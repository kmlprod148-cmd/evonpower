@extends('layouts.app')

@section('title', 'Business Profils - EVON')
@section('page-title', 'Business Profils')

@php
use Illuminate\Pagination\Paginator;
@endphp

@push('styles')
<style>
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
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .status-inactive {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .status-public {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
    }
    
    .status-private {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
    }
    
    .status-integrator {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
    }
    
    .status-operator {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    
    .action-btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .action-btn-warning:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        color: white;
    }
</style>
@endpush

@section('content')
@php
    $businessProfileRoutePrefix = $businessProfileRoutePrefix ?? 'business-profiles';
@endphp
<div class="space-y-6">
    <!-- Header Section -->
    <div class="gradient-bg text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">
                    <i class="fas fa-building mr-3"></i>
                    Business Profils
                </h1>
                <p class="text-green-100">
                    Gérez et configurez tous les profils business de votre système
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Create Button in Header -->
                @can('create_business_profiles')
                <a href="{{ route($businessProfileRoutePrefix . '.create') }}" 
                   class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg flex items-center transition-all duration-200 border border-white border-opacity-30">
                    <i class="fas fa-plus mr-2"></i>
                    Nouveau Profil
                </a>
                @elseif(auth()->user()->hasRole('integrator'))
                <a href="{{ route($businessProfileRoutePrefix . '.create') }}" 
                   class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg flex items-center transition-all duration-200 border border-white border-opacity-30">
                    <i class="fas fa-plus mr-2"></i>
                    Nouveau Profil
                </a>
                @endif
                
                <!-- Stats -->
                <div class="hidden sm:block text-right">
                    <div class="text-3xl font-bold">{{ $businessProfiles->count() }}</div>
                    <div class="text-green-100 text-sm">Profils actifs</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-building text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $businessProfiles->count() }}</div>
                    <div class="text-sm text-gray-600">Total Profils</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $businessProfiles->where('is_active', true)->count() }}</div>
                    <div class="text-sm text-gray-600">Actifs</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-globe text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $businessProfiles->where('is_public', true)->count() }}</div>
                    <div class="text-sm text-gray-600">Publics</div>
                </div>
            </div>
        </div>
        
        <div class="stats-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $businessProfiles->where('target_type', 'integrator')->count() }}</div>
                    <div class="text-sm text-gray-600">Intégrateurs</div>
                </div>
            </div>
        </div>
        
        <!-- Create Profile Action Card -->
        @can('create_business_profiles')
        <div class="stats-card p-6 cursor-pointer hover:shadow-lg transition-all duration-200" onclick="window.location.href='{{ route($businessProfileRoutePrefix . '.create') }}'">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-plus text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-lg font-bold text-gray-900">Créer</div>
                    <div class="text-sm text-gray-600">Nouveau Profil</div>
                </div>
            </div>
        </div>
        @elseif(auth()->user()->hasRole('integrator'))
        <div class="stats-card p-6 cursor-pointer hover:shadow-lg transition-all duration-200" onclick="window.location.href='{{ route($businessProfileRoutePrefix . '.create') }}'">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-plus text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-lg font-bold text-gray-900">Créer</div>
                    <div class="text-sm text-gray-600">Nouveau Profil</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Business Profiles List -->
    @if($businessProfiles->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-list mr-2 text-green-500"></i>
                        Liste des Business Profils
                    </h2>
                    <div class="flex items-center space-x-2">
                        @can('create_business_profiles')
                        <a href="{{ route($businessProfileRoutePrefix . '.create') }}" class="action-btn action-btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Nouveau Profil
                        </a>
                        @elseif(auth()->user()->hasRole('integrator'))
                        <a href="{{ route($businessProfileRoutePrefix . '.create') }}" class="action-btn action-btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Nouveau Profil
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Profil
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Visibilité
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($businessProfiles as $profile)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $profile->id }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                                {{ strtoupper(substr($profile->name, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $profile->name }}</div>
                                            @if($profile->description)
                                                <div class="text-sm text-gray-500 truncate max-w-xs">{{ $profile->description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="status-badge {{ $profile->target_type == 'integrator' ? 'status-integrator' : 'status-operator' }}">
                                        {{ $profile->target_type == 'integrator' ? 'Intégrateurs' : 'Opérateurs' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="status-badge {{ $profile->is_public ? 'status-public' : 'status-private' }}">
                                        {{ $profile->is_public ? 'Public' : 'Privé' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="status-badge {{ $profile->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $profile->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route($businessProfileRoutePrefix . '.show', $profile) }}" 
                                           class="action-btn action-btn-secondary text-xs">
                                            <i class="fas fa-eye mr-1"></i>
                                            Voir
                                        </a>
                                        
                                        @can('update', $profile)
                                        <a href="{{ route($businessProfileRoutePrefix . '.edit', $profile) }}" 
                                           class="action-btn action-btn-secondary text-xs">
                                            <i class="fas fa-edit mr-1"></i>
                                            Modifier
                                        </a>
                                        @endcan

                                        <a href="{{ route($businessProfileRoutePrefix . '.manage-partner-rates', $profile) }}" 
                                           class="action-btn action-btn-warning text-xs">
                                            <i class="fas fa-cog mr-1"></i>
                                            Taux
                                        </a>
                                        
                                        @can('delete', $profile)
                                        <form method="POST" action="{{ route($businessProfileRoutePrefix . '.destroy', $profile) }}" 
                                              class="inline-block"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce profil ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-danger text-xs">
                                                <i class="fas fa-trash mr-1"></i>
                                                Supprimer
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-building"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                Aucun business profil trouvé
            </h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                Il n'y a actuellement aucun business profil dans votre système. 
                Commencez par créer votre premier profil.
            </p>
            @can('create_business_profiles')
            <a href="{{ route($businessProfileRoutePrefix . '.create') }}" class="action-btn action-btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Créer le premier profil
            </a>
            @elseif(auth()->user()->hasRole('integrator'))
            <a href="{{ route($businessProfileRoutePrefix . '.create') }}" class="action-btn action-btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Créer le premier profil
            </a>
            @endif
        </div>
    @endif
</div>
@endsection
