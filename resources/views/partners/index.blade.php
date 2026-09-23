@extends('layouts.app')

@section('title', 'Partenaires/Opérateurs - EVON')
@section('page-title', 'Partenaires/Opérateurs')

@push('styles')
<style>
    /* Fix scroll to end */
    .partners-content {
        padding-bottom: 120px;
    }
    
    @media (max-width: 768px) {
        .partners-content {
            padding-bottom: 160px;
        }
    }
    
    .gradient-bg {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    /* Table wrapper for horizontal scroll */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-exploitant {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
    }
    
    .status-proprietaire {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .status-integrateur {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
    }
    
    .stats-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px;
        border: 2px dashed #cbd5e1;
    }
    
    .empty-state-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: #64748b;
        font-size: 32px;
    }
    
    .action-btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    
    .action-btn-primary {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .action-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }
    
    .action-btn-secondary {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .action-btn-secondary:hover {
        background: #e2e8f0;
        color: #374151;
    }
    
    .action-btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .action-btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="space-y-6 partners-content">
    <!-- Header Section -->
    <div class="gradient-bg text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">
                    <i class="fas fa-handshake mr-3"></i>
                    Partenaires/Opérateurs
                </h1>
                <p class="text-green-100">
                    Gérez et visualisez tous les partenaires et opérateurs de votre système
                </p>
            </div>
            <div class="hidden sm:block">
                <div class="text-right">
                    <div class="text-3xl font-bold">{{ $partners->total() }}</div>
                    <div class="text-green-100 text-sm">Partenaires actifs</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages de succès/erreur -->
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif
    
    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p>{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-handshake text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $partners->total() }}</div>
                    <div class="text-sm text-gray-600">Total Partenaires</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-cogs text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $partners->where('type', 'Exploitant')->count() }}</div>
                    <div class="text-sm text-gray-600">Exploitants</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-home text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $partners->where('type', 'Propriétaire')->count() }}</div>
                    <div class="text-sm text-gray-600">Propriétaires</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $partners->where('type', 'Intégrateur')->count() }}</div>
                    <div class="text-sm text-gray-600">Intégrateurs</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Partners List -->
    @if($partners->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-list mr-2 text-green-500"></i>
                        Liste des Partenaires
                    </h2>
                    <div class="flex items-center space-x-2">
                        <!-- Barre de recherche -->
                        <form action="{{ route('partners.index') }}" method="GET" class="flex items-center">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       placeholder="Rechercher..." 
                                       value="{{ request('search') }}"
                                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                
                                @if(request()->has('type'))
                                    <input type="hidden" name="type" value="{{ request('type') }}">
                                @endif
                                
                                <button type="submit" class="ml-2 action-btn action-btn-secondary text-xs">
                                    <i class="fas fa-search mr-1"></i>
                                    Rechercher
                                </button>
                            </div>
                        </form>
                        
                        <!-- Filtre par type -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="action-btn action-btn-secondary">
                                <i class="fas fa-filter mr-2"></i>
                                {{ request('type') ? ucfirst(request('type')) : 'Filtrer' }}
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10 border border-gray-200">
                                <div class="py-1">
                                    <a href="{{ route('partners.index', array_merge(request()->except(['type', 'page']), ['type' => ''])) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-list mr-2"></i>
                                        Tous
                                    </a>
                                    <a href="{{ route('partners.index', array_merge(request()->except('page'), ['type' => 'exploitant'])) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-cogs mr-2"></i>
                                        Exploitant
                                    </a>
                                    <a href="{{ route('partners.index', array_merge(request()->except('page'), ['type' => 'proprietaire'])) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-home mr-2"></i>
                                        Propriétaire
                                    </a>
                                    <a href="{{ route('partners.index', array_merge(request()->except('page'), ['type' => 'integrateur'])) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-users mr-2"></i>
                                        Intégrateur
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        @if(auth()->user()->hasRole(['integrator','Integrator']) || auth()->user()->can('create_partners'))
                        <a href="{{ route('partners.create') }}" class="action-btn action-btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Nouveau Partenaire
                        </a>
                        @endif

                        @can('create', \App\Models\User::class)
                        <a href="{{ route('integrator.operators.create') }}" class="action-btn action-btn-secondary">
                            <i class="fas fa-user-plus mr-2"></i>
                            Créer Opérateur
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            
            <div class="px-6 pb-4">
                <form method="GET" action="{{ route('partners.index') }}" class="flex flex-wrap gap-3 items-center text-sm">
                    <div>
                        <label class="block text-gray-600 mb-1">Ville</label>
                        <select name="city" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="">Toutes</option>
                            @isset($cities)
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    @isset($integrators)
                    <div>
                        <label class="block text-gray-600 mb-1">Intégrateur</label>
                        <select name="integrator_id" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="">Tous</option>
                            @foreach($integrators as $integrator)
                                <option value="{{ $integrator->id }}" {{ (string)request('integrator_id') === (string)$integrator->id ? 'selected' : '' }}>
                                    {{ $integrator->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endisset
                </form>
            </div>

            <div class="table-wrapper overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Partenaire
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ville
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bornes
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($partners as $partner)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $partner->id }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                                {{ strtoupper(substr($partner->name, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $partner->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $partner->contact_name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="status-badge 
                                        @if($partner->type == 'Exploitant') status-exploitant
                                        @elseif($partner->type == 'Propriétaire') status-proprietaire
                                        @else status-integrateur @endif">
                                        {{ $partner->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $partner->city ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $partner->charging_points_count ?? $partner->stations_count ?? 0 }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('partners.show', $partner) }}" 
                                           class="action-btn action-btn-secondary text-xs">
                                            <i class="fas fa-eye mr-1"></i>
                                            Voir
                                        </a>
                                        <a href="{{ route('partners.edit', $partner) }}" 
                                           class="action-btn action-btn-secondary text-xs">
                                            <i class="fas fa-edit mr-1"></i>
                                            Modifier
                                        </a>
                                        <form method="POST" action="{{ route('partners.destroy', $partner) }}" 
                                              class="inline-block"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-danger text-xs">
                                                <i class="fas fa-trash mr-1"></i>
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($partners->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Affichage de {{ $partners->firstItem() ?? 0 }} à {{ $partners->lastItem() ?? 0 }} sur {{ $partners->total() ?? 0 }} résultats
                    </div>
                    <div>
                        {{ $partners->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    @else
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-handshake"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                Aucun partenaire trouvé
            </h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                Il n'y a actuellement aucun partenaire dans votre système. 
                Commencez par créer votre premier partenaire.
            </p>
            @if(auth()->user()->hasRole(['integrator','Integrator']) || auth()->user()->can('create_partners'))
            <a href="{{ route('partners.create') }}" class="action-btn action-btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Créer le premier partenaire
            </a>
            @endif

            @can('create', \App\Models\User::class)
            <a href="{{ route('integrator.operators.create') }}" class="action-btn action-btn-secondary ml-2">
                <i class="fas fa-user-plus mr-2"></i>
                Créer un opérateur
            </a>
            @endcan
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
@endpush
