@extends('layouts.app')

@section('content')
<div class="partner-show-container">
    <div class="text-gray-500 text-sm">Mes partenaires</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('integrator.partners.index') }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium mr-4">{{ $partner->name ?? 'Détail du partenaire' }}</h1>
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ isset($partner) && $partner->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ isset($partner) && $partner->is_active ? 'Actif' : 'Inactif' }}
        </span>
    </div>
    
    <div class="flex space-x-4 mb-6">
        <a href="{{ route('integrator.partners.edit', $partner ?? 1) }}" class="bg-green-500 text-white px-4 py-2 rounded-lg flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
        </a>
    </div>
    
    <!-- Main content -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Partner Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Informations du partenaire</h2>
            
            <div class="border-t border-gray-100 pt-4">
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Type</span>
                    <span class="font-medium">{{ $partner->type ?? 'Exploitant' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Email</span>
                    <span class="font-medium">{{ $partner->email ?? 'contact@example.com' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Téléphone</span>
                    <span class="font-medium">{{ $partner->phone ?? '+212 5XX XXX XXX' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Adresse</span>
                    <span class="font-medium">{{ $partner->address ?? 'Adresse du partenaire' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Ville</span>
                    <span class="font-medium">{{ $partner->city ?? 'Ville' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Pays</span>
                    <span class="font-medium">{{ $partner->country ?? 'Maroc' }}</span>
                </div>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Statistiques</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Bornes totales</span>
                    <span class="text-2xl font-bold text-blue-600">{{ $stats['total_charging_points'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Bornes actives</span>
                    <span class="text-2xl font-bold text-green-600">{{ $stats['active_charging_points'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Transactions</span>
                    <span class="text-2xl font-bold text-purple-600">{{ $stats['total_transactions'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Business Profile Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Profil Business</h2>
            
            @if($partner->businessProfile)
                <div class="space-y-3">
                    <div>
                        <span class="text-gray-500 text-sm">Nom du profil</span>
                        <p class="font-medium">{{ $partner->businessProfile->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm">Description</span>
                        <p class="text-sm">{{ $partner->businessProfile->description ?? 'Aucune description' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm">Statut</span>
                        <span class="px-2 py-1 text-xs rounded-full {{ $partner->businessProfile->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $partner->businessProfile->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
            @else
                <p class="text-gray-500 text-sm">Aucun profil business assigné</p>
            @endif
        </div>
    </div>

    <!-- Charging Points Section -->
    <div class="mt-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium">Bornes de recharge</h2>
                <a href="{{ route('integrator.charging-points.create') }}" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm">
                    Ajouter une borne
                </a>
            </div>
            
            @if($partner->chargingPoints && $partner->chargingPoints->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($partner->chargingPoints as $chargingPoint)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-medium">{{ $chargingPoint->name }}</h3>
                                <span class="px-2 py-1 text-xs rounded-full {{ $chargingPoint->status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $chargingPoint->status === 'online' ? 'En ligne' : 'Hors ligne' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $chargingPoint->location ?? 'Localisation non définie' }}</p>
                            <div class="mt-2 flex space-x-2">
                                <a href="{{ route('integrator.charging-points.show', $chargingPoint) }}" class="text-blue-500 hover:text-blue-700 text-sm">
                                    Voir
                                </a>
                                <a href="{{ route('integrator.charging-points.edit', $chargingPoint) }}" class="text-green-500 hover:text-green-700 text-sm">
                                    Modifier
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0h4m-4 0V7a2 2 0 012-2h4a2 2 0 012 2v2M7 7h4m-4 0v4m4-4v4" />
                    </svg>
                    <p class="text-gray-500">Aucune borne de recharge associée</p>
                    <a href="{{ route('integrator.charging-points.create') }}" class="mt-2 inline-block bg-green-500 text-white px-4 py-2 rounded-lg text-sm">
                        Ajouter la première borne
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
