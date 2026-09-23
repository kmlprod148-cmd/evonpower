@extends('layouts.app')

@section('title', 'Gestion des Intégrateurs')

@section('styles')
<link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
<style>
    .status-badge {
        @apply px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full;
    }
    .status-active {
        @apply bg-green-100 text-green-800;
    }
    .status-inactive {
        @apply bg-gray-100 text-gray-800;
    }
</style>
@endsection

@section('content')
<div class="py-6">
    <!-- Header avec titre et bouton d'ajout -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Gestion des Intégrateurs</h1>
            <div class="flex space-x-2">
                <!-- Bouton pour créer un intégrateur -->
                <a href="{{ route('integrators.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i> Ajouter un intégrateur
                </a>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Messages flash -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Intégrateurs Actifs</p>
                        <p class="text-2xl font-semibold">{{ $stats['active_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-plug text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Bornes Connectées</p>
                        <p class="text-2xl font-semibold">{{ $stats['stations_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Nouveaux ce mois</p>
                        <p class="text-2xl font-semibold">{{ $stats['new_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium">Filtres et Recherche</h2>
            </div>
            <div class="p-6">
                <form action="{{ route('integrators.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                        <input type="text" id="search" name="search" placeholder="Nom, email, téléphone..." 
                               value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                            <option value="">Tous les statuts</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                        <select id="city" name="city" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                            <option value="">Toutes les villes</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-search mr-2"></i> Rechercher
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table des intégrateurs -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium">Liste des Intégrateurs</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('integrators.export') }}" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        <i class="fas fa-download mr-1"></i> Exporter
                    </a>
                    <form action="{{ route('integrators.index') }}" method="GET" class="inline-flex items-center">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="city" value="{{ request('city') }}">
                        <select name="per_page" onchange="this.form.submit()" 
                                class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50 text-sm">
                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 par page</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 par page</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 par page</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <a href="{{ route('integrators.index', array_merge(request()->except(['sort', 'order']), ['sort' => 'name', 'order' => request('sort') === 'name' && request('order') === 'asc' ? 'desc' : 'asc'])) }}">
                                        Nom
                                        @if(request('sort') === 'name')
                                            <i class="fas fa-sort-{{ request('order') === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @else
                                            <i class="fas fa-sort ml-1"></i>
                                        @endif
                                    </a>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <a href="{{ route('integrators.index', array_merge(request()->except(['sort', 'order']), ['sort' => 'city', 'order' => request('sort') === 'city' && request('order') === 'asc' ? 'desc' : 'asc'])) }}">
                                        Ville
                                        @if(request('sort') === 'city')
                                            <i class="fas fa-sort-{{ request('order') === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @else
                                            <i class="fas fa-sort ml-1"></i>
                                        @endif
                                    </a>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <a href="{{ route('integrators.index', array_merge(request()->except(['sort', 'order']), ['sort' => 'is_active', 'order' => request('sort') === 'is_active' && request('order') === 'asc' ? 'desc' : 'asc'])) }}">
                                        Statut
                                        @if(request('sort') === 'is_active')
                                            <i class="fas fa-sort-{{ request('order') === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @else
                                            <i class="fas fa-sort ml-1"></i>
                                        @endif
                                    </a>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <a href="{{ route('integrators.index', array_merge(request()->except(['sort', 'order']), ['sort' => 'stations_count', 'order' => request('sort') === 'stations_count' && request('order') === 'asc' ? 'desc' : 'asc'])) }}">
                                        Stations
                                        @if(request('sort') === 'stations_count')
                                            <i class="fas fa-sort-{{ request('order') === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @else
                                            <i class="fas fa-sort ml-1"></i>
                                        @endif
                                    </a>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @if($integrators->isEmpty())
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Aucun intégrateur trouvé
                                </td>
                            </tr>
                        @else
                            @foreach($integrators as $integrator)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full
                                                        {{ $integrator->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                                <span class="font-medium">{{ substr($integrator->name, 0, 1) }}</span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium">{{ $integrator->name }}</div>
                                                <div class="text-sm text-gray-500">#INT-{{ str_pad($integrator->id, 3, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $integrator->email }}</div>
                                        <div class="text-sm text-gray-500">{{ $integrator->phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $integrator->city }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge {{ $integrator->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $integrator->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                        {{ $integrator->stations_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <div class="flex justify-center space-x-2">
                                            <!-- Bouton Voir -->
                                            <a href="{{ route('integrators.show', $integrator) }}" class="text-indigo-600 hover:text-indigo-900" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <!-- Bouton Modifier -->
                                            <a href="{{ route('integrators.edit', $integrator) }}" class="text-green-600 hover:text-green-900" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Bouton Activer/Désactiver -->
                                            <form action="{{ $integrator->is_active ? route('integrators.deactivate', $integrator) : route('integrators.activate', $integrator) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="{{ $integrator->is_active ? 'text-red-600 hover:text-red-900' : 'text-gray-600 hover:text-gray-900' }}" title="{{ $integrator->is_active ? 'Désactiver' : 'Activer' }}">
                                                    <i class="fas fa-toggle-{{ $integrator->is_active ? 'on' : 'off' }}"></i>
                                                </button>
                                            </form>

                                            <!-- Bouton Supprimer -->
                                            <form action="{{ route('integrators.destroy', $integrator) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet intégrateur ?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $integrators->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Script pour confirmer la désactivation d'un intégrateur
    document.addEventListener('DOMContentLoaded', function() {
        const toggleForms = document.querySelectorAll('form[action*="deactivate"]');
        
        toggleForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (confirm('Êtes-vous sûr de vouloir désactiver cet intégrateur ? Toutes ses bornes seront également désactivées.')) {
                    this.submit();
                }
            });
        });
    });
</script>
@endsection