@extends('layouts.app')

@section('title', 'Mes Charging Points')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Mes Charging Points</h1>
        <a href="{{ route('operator.charging-points.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
            <i class="fas fa-plus mr-2"></i>Créer un Charging Point
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($chargingPoints->count() > 0)
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach($chargingPoints as $chargingPoint)
                    <li class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ $chargingPoint->name }}
                                    </h3>
                                    @if($chargingPoint->is_public)
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Public
                                        </span>
                                    @else
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Privé
                                        </span>
                                    @endif
                                    
                                    @if($chargingPoint->is_active)
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Actif
                                        </span>
                                    @else
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactif
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $chargingPoint->location }}
                                </p>
                                
                                <div class="mt-2 text-sm text-gray-500">
                                    <span>Puissance max: {{ $chargingPoint->max_power }} kW</span>
                                    <span class="ml-4">Connecteur: {{ $chargingPoint->connector_type }}</span>
                                    @if($chargingPoint->pricingPlan)
                                        <span class="ml-4">Plan: {{ $chargingPoint->pricingPlan->name }}</span>
                                    @endif
                                </div>
                                
                                <div class="mt-2 text-sm text-gray-500">
                                    <span>Créé le {{ $chargingPoint->created_at->format('d/m/Y à H:i') }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('operator.charging-points.show', $chargingPoint) }}" 
                                   class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    Voir
                                </a>
                                <a href="{{ route('operator.charging-points.edit', $chargingPoint) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Modifier
                                </a>
                                
                                <form action="{{ route('operator.charging-points.toggle-status', $chargingPoint) }}" 
                                      method="POST" 
                                      class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-{{ $chargingPoint->is_active ? 'red' : 'green' }}-600 hover:text-{{ $chargingPoint->is_active ? 'red' : 'green' }}-900 text-sm font-medium">
                                        {{ $chargingPoint->is_active ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>
                                
                                <form action="{{ route('operator.charging-points.destroy', $chargingPoint) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce charging point ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            {{ $chargingPoints->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <i class="fas fa-charging-station text-4xl"></i>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun charging point</h3>
            <p class="mt-1 text-sm text-gray-500">
                Commencez par créer votre premier charging point.
            </p>
            <div class="mt-6">
                <a href="{{ route('operator.charging-points.create') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus -ml-1 mr-2 h-5 w-5"></i>
                    Créer un Charging Point
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
