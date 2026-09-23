@extends('layouts.app')

@section('title', 'Détails Intégrateur - ' . $integrator->name)
@section('page-title', 'Détails Intégrateur')

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
    
    .info-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    
    .info-card:hover {
        border-color: #10b981;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
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
<div class="space-y-6">
    <!-- Header Section -->
    <div class="gradient-bg text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-white">
                        {{ strtoupper(substr($integrator->name, 0, 2)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold mb-2">{{ $integrator->name }}</h1>
                    <p class="text-green-100">{{ $integrator->email }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="status-badge {{ $integrator->is_active ? 'status-active' : 'status-inactive' }}">
                    {{ $integrator->is_active ? 'Actif' : 'Inactif' }}
                </span>
                <div class="flex space-x-2">
                    <a href="{{ route('integrators.edit', $integrator) }}" class="action-btn action-btn-primary">
                        <i class="fas fa-edit mr-2"></i>
                        Modifier
                    </a>
                    <a href="{{ route('integrators.index') }}" class="action-btn action-btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations Générales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informations de Contact -->
        <div class="info-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-address-book text-green-500 mr-2"></i>
                Informations de Contact
            </h2>
            <div class="space-y-3">
                <div class="flex items-center">
                    <i class="fas fa-envelope text-gray-400 w-5 mr-3"></i>
                    <span class="text-gray-900">{{ $integrator->email }}</span>
                </div>
                @if($integrator->phone)
                <div class="flex items-center">
                    <i class="fas fa-phone text-gray-400 w-5 mr-3"></i>
                    <span class="text-gray-900">{{ $integrator->phone }}</span>
                </div>
                @endif
                @if($integrator->contact_name)
                <div class="flex items-center">
                    <i class="fas fa-user text-gray-400 w-5 mr-3"></i>
                    <span class="text-gray-900">{{ $integrator->contact_name }}</span>
                </div>
                @endif
                @if($integrator->website)
                <div class="flex items-center">
                    <i class="fas fa-globe text-gray-400 w-5 mr-3"></i>
                    <a href="{{ $integrator->website }}" target="_blank" class="text-green-600 hover:text-green-800">
                        {{ $integrator->website }}
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Adresse -->
        <div class="info-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-map-marker-alt text-green-500 mr-2"></i>
                Adresse
            </h2>
            <div class="space-y-3">
                @if($integrator->address)
                <div class="flex items-start">
                    <i class="fas fa-home text-gray-400 w-5 mr-3 mt-1"></i>
                    <span class="text-gray-900">{{ $integrator->address }}</span>
                </div>
                @endif
                @if($integrator->city)
                <div class="flex items-center">
                    <i class="fas fa-city text-gray-400 w-5 mr-3"></i>
                    <span class="text-gray-900">{{ $integrator->city }}</span>
                </div>
                @endif
                @if($integrator->postal_code)
                <div class="flex items-center">
                    <i class="fas fa-mail-bulk text-gray-400 w-5 mr-3"></i>
                    <span class="text-gray-900">{{ $integrator->postal_code }}</span>
                </div>
                @endif
                @if($integrator->country)
                <div class="flex items-center">
                    <i class="fas fa-flag text-gray-400 w-5 mr-3"></i>
                    <span class="text-gray-900">{{ $integrator->country }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Description -->
    @if($integrator->description)
    <div class="info-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-green-500 mr-2"></i>
            Description
        </h2>
        <p class="text-gray-700 leading-relaxed">{{ $integrator->description }}</p>
    </div>
    @endif

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="info-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $integrator->operators->count() }}</div>
                    <div class="text-sm text-gray-600">Opérateurs</div>
                </div>
            </div>
        </div>
        
        <div class="info-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-bolt text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $integrator->chargingPoints->count() }}</div>
                    <div class="text-sm text-gray-600">Points de Charge</div>
                </div>
            </div>
        </div>
        
        <div class="info-card p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-handshake text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $integrator->partners->count() }}</div>
                    <div class="text-sm text-gray-600">Partenaires</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations Système -->
    <div class="info-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cog text-green-500 mr-2"></i>
            Informations Système
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <i class="fas fa-calendar text-gray-400 w-5 mr-3"></i>
                        <span class="text-gray-900">Créé le {{ $integrator->created_at->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-clock text-gray-400 w-5 mr-3"></i>
                        <span class="text-gray-900">Dernière modification {{ $integrator->updated_at->format('d/m/Y à H:i') }}</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <i class="fas fa-user-plus text-gray-400 w-5 mr-3"></i>
                        <span class="text-gray-900">Créé par l'utilisateur #{{ $integrator->created_by ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-id-badge text-gray-400 w-5 mr-3"></i>
                        <span class="text-gray-900">ID: {{ $integrator->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
        <div class="flex items-center space-x-4">
            @can('edit_integrators')
                @if($integrator->is_active)
                    <form method="POST" action="{{ route('integrators.deactivate', $integrator) }}" class="inline">
                        @csrf
                        <button type="submit" class="action-btn action-btn-secondary">
                            <i class="fas fa-pause mr-2"></i>
                            Désactiver
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('integrators.activate', $integrator) }}" class="inline">
                        @csrf
                        <button type="submit" class="action-btn action-btn-primary">
                            <i class="fas fa-play mr-2"></i>
                            Activer
                        </button>
                    </form>
                @endif
            @endcan
        </div>
        
        <div class="flex items-center space-x-2">
            @can('delete_integrators')
            <form method="POST" action="{{ route('integrators.destroy', $integrator) }}" 
                  class="inline"
                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet intégrateur ? Cette action est irréversible.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="action-btn action-btn-danger">
                    <i class="fas fa-trash mr-2"></i>
                    Supprimer
                </button>
            </form>
            @endcan
        </div>
    </div>
</div>
@endsection