@extends('layouts.app')

@section('content')
<div class="partner-show-container">
    <div class="text-gray-500 text-sm">Liste des partenaires</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('partners.index') }}" class="text-gray-500 hover:text-gray-700 mr-2">
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
        <a href="{{ route('partners.edit', $partner ?? 1) }}" class="bg-green-500 text-white px-4 py-2 rounded-lg flex items-center">
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
                    <span class="font-medium">{{ $partner->city ?? 'Casablanca' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Code postal</span>
                    <span class="font-medium">{{ $partner->postal_code ?? '20000' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Date d'inscription</span>
                    <span class="font-medium">{{ isset($partner) && $partner->created_at ? $partner->created_at->format('d/m/Y') : '01/01/2023' }}</span>
                </div>
            </div>
        </div>

        <!-- Business Profile Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Profil d'affaires</h2>
            @if($partner->businessProfile)
                <div class="border-t border-gray-100 pt-4">
                    <div class="flex justify-between py-2">
                        <span class="text-gray-500">Nom</span>
                        <span class="font-medium">{{ $partner->businessProfile->name }}</span>
                    </div>
                    <!-- Add other business profile details here -->
                </div>
            @else
                <p>Aucun profil d'affaires associé.</p>
            @endif
        </div>
        
        <!-- Contact Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Informations de contact</h2>
            
            <div class="flex items-center mb-4">
                <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold text-lg mr-3">
                    {{ substr($partner->contact_name ?? 'Contact Name', 0, 1) }}
                </div>
                <div>
                    <h3 class="font-medium">{{ $partner->contact_name ?? 'Nom du contact' }}</h3>
                    <p class="text-gray-500 text-sm">{{ $partner->contact_title ?? 'Responsable' }}</p>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-start mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ $partner->contact_email ?? 'contact@example.com' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Téléphone</p>
                        <p class="font-medium">{{ $partner->contact_phone ?? '+212 6XX XXX XXX' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-medium mb-4">Statistiques</h2>
            
            <div class="border-t border-gray-100 pt-4">
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Bornes de recharge</span>
                    <span class="font-medium">{{ $partner->stations_count ?? 15 }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Bornes actives</span>
                    <span class="font-medium">{{ $partner->active_stations_count ?? 12 }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Bornes en maintenance</span>
                    <span class="font-medium">{{ $partner->maintenance_stations_count ?? 3 }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Sessions de recharge</span>
                    <span class="font-medium">{{ $partner->charging_sessions_count ?? 425 }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">kWh délivrés</span>
                    <span class="font-medium">{{ $partner->delivered_kwh ?? '7,842' }} kWh</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charging Stations Section -->
    <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-medium mb-4">Bornes de recharge</h2>
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localisation</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($stations ?? [] as $station)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $station->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $station->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $station->location }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $station->type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $station->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($station->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="#" class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <!-- Show sample data if no stations exist -->
                @foreach([
                    [1, 'Station 01', 'Parking Nord', 'Type 2', 'active'],
                    [2, 'Station 02', 'Parking Est', 'CCS', 'active'],
                    [3, 'Station 03', 'Parking Ouest', 'CHAdeMO', 'maintenance'],
                    [4, 'Station 04', 'Parking Sud', 'Type 2', 'active']
                ] as $station)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $station[0] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $station[1] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $station[2] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $station[3] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $station[4] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($station[4]) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="#" class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
                @endforelse
            </tbody>
        </table>
        
        <div class="flex justify-end mt-4">
            <a href="#" class="text-green-500 hover:text-green-700 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Ajouter une borne
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background-color: #f9fafb;
    }
</style>
@endpush