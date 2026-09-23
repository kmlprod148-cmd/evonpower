@extends('layouts.app')

@section('content')
<div class="integrator-show-container">
    <div class="text-gray-500 text-sm">Intégrateurs</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route('integrators.index') ?? '#' }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium mr-4">{{ $integrator->name ?? 'EcoSolutions' }}</h1>
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ isset($integrator) && ($integrator->active ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ isset($integrator) && ($integrator->active ?? true) ? 'Actif' : 'Inactif' }}
        </span>
    </div>
    
    <div class="flex space-x-4 mb-6">
        <a href="{{ route('integrators.edit', $integrator ?? 1) ?? '#' }}" class="bg-green-500 text-white px-4 py-2 rounded-lg flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
        </a>
        
        <form action="{{ route('integrators.destroy', $integrator ?? 1) ?? '#' }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg flex items-center" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet intégrateur?')">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Supprimer
            </button>
        </form>
    </div>
    
    <!-- Main content -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Profile Summary -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center mb-6">
                @if(isset($integrator) && $integrator->logo)
                    <img src="{{ asset('storage/' . $integrator->logo) }}" alt="Logo" class="h-16 w-16 rounded-full object-cover mr-4">
                @else
                    <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center text-green-500 font-bold text-2xl mr-4">
                        {{ substr($integrator->name ?? 'EcoSolutions', 0, 1) }}
                    </div>
                @endif
                
                <div>
                    <h2 class="text-xl font-medium">{{ $integrator->name ?? 'EcoSolutions' }}</h2>
                    <p class="text-gray-500">{{ $integrator->specialization ?? 'Commercial' }}</p>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-4">
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Expérience</span>
                    <span class="font-medium">{{ $integrator->experience ?? '8' }} ans</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Projets</span>
                    <span class="font-medium">{{ $integrator->projects_count ?? '12' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Certifications</span>
                    <span class="font-medium">{{ $integrator->certifications ?? 'ISO 9001, ISO 14001' }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Date d'inscription</span>
                    <span class="font-medium">{{ isset($integrator) && $integrator->created_at ? $integrator->created_at->format('d/m/Y') : '15/05/2022' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-medium mb-4">Coordonnées</h3>
            
            <div class="grid grid-cols-1 gap-4">
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ $integrator->email ?? 'contact@ecosolutions.com' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Téléphone</p>
                        <p class="font-medium">{{ $integrator->phone ?? '+212 522 123 456' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Site web</p>
                        <p class="font-medium">{{ $integrator->website ?? 'www.ecosolutions.com' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Adresse</p>
                        <p class="font-medium">{{ $integrator->address ?? '123 Boulevard Mohammed V' }}<br>
                        {{ $integrator->city ?? 'Casablanca' }}, {{ $integrator->postal_code ?? '20000' }}<br>
                        {{ $integrator->country ?? 'Maroc' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Person -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-medium mb-4">Personne de contact</h3>
            
            <div class="flex items-center mb-4">
                <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold text-lg mr-3">
                    {{ substr($integrator->contact_name ?? 'Mohammed Alami', 0, 1) }}
                </div>
                
                <div>
                    <h4 class="font-medium">{{ $integrator->contact_name ?? 'Mohammed Alami' }}</h4>
                    <p class="text-gray-500 text-sm">{{ $integrator->contact_title ?? 'Directeur Général' }}</p>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-start mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ $integrator->contact_email ?? 'm.alami@ecosolutions.com' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Téléphone</p>
                        <p class="font-medium">{{ $integrator->contact_phone ?? '+212 661 123 456' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-4">
        <!-- Description -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-medium mb-4">À propos de l'entreprise</h3>
            <p class="text-gray-700">{{ $integrator->description ?? 'EcoSolutions est une entreprise spécialisée dans l\'installation et la maintenance des solutions de recharge pour véhicules électriques. Avec une expérience de plus de 8 ans dans le secteur, nous avons réalisé plus de 200 installations à travers le Maroc, et nous continuons à développer notre expertise pour offrir les meilleures solutions à nos clients. Notre équipe est composée de professionnels certifiés qui s\'engagent à fournir un service de qualité supérieure et des conseils personnalisés pour chaque projet.' }}</p>
        </div>
        
        <!-- Projects -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-medium">Projets récents</h3>
                <a href="#" class="text-green-500 text-sm">Voir tous les projets</a>
            </div>
            
            @php
                $projects = isset($integrator) && $integrator->projects ? $integrator->projects : [
                    ['name' => 'Morocco Mall - Casablanca', 'stations' => 12, 'date' => '15/04/2023'],
                    ['name' => 'Hôtel Royal Mansour - Marrakech', 'stations' => 8, 'date' => '22/02/2023'],
                    ['name' => 'Complexe résidentiel Les Jardins - Rabat', 'stations' => 15, 'date' => '10/01/2023'],
                ];
            @endphp
            
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom du projet</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bornes</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($projects as $project)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $project['name'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project['stations'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project['date'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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