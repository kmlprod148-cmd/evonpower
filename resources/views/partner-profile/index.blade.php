@extends('layouts.app')

@section('content')
<div class="partners-index-container">
    <div class="text-gray-500 text-sm">Partenaires</div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-medium">Liste des partenaires</h1>
        @if(auth()->user()->hasRole(['integrator','Integrator']) || auth()->user()->can('create_partners'))
        <div class="flex items-center space-x-3">
            <a href="{{ route('partners.create') ?? '#' }}" class="bg-green-500 text-white px-4 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Ajouter un partenaire
            </a>
            @can('create', \App\Models\User::class)
            <a href="{{ route('integrator.operators.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Créer un opérateur
            </a>
            @endcan
        </div>
        @endif
    </div>
    
    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm relative stats-card">
            <div class="text-gray-500 text-sm">Total des partenaires</div>
            <div class="text-3xl font-medium mt-3">{{ $total_partners ?? 28 }}</div>
            <div class="w-10 h-10 rounded-full bg-green-100 absolute right-4 top-1/2 transform -translate-y-1/2"></div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm relative stats-card">
            <div class="text-gray-500 text-sm">Partenaires actifs</div>
            <div class="text-3xl font-medium mt-3">{{ $active_partners ?? 24 }}</div>
            <div class="w-10 h-10 rounded-full bg-green-100 absolute right-4 top-1/2 transform -translate-y-1/2"></div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm relative stats-card">
            <div class="text-gray-500 text-sm">Bornes gérées</div>
            <div class="text-3xl font-medium mt-3">{{ $managed_stations ?? 156 }}</div>
            <div class="w-10 h-10 rounded-full bg-green-100 absolute right-4 top-1/2 transform -translate-y-1/2"></div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <div class="text-lg font-medium">Liste des partenaires</div>
            <div class="flex space-x-3">
                <div class="relative w-64">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Rechercher" class="pl-10 pr-4 py-2 border border-gray-200 rounded-md bg-white text-sm w-full">
                </div>
                <button class="flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filtrer
                </button>
            </div>
        </div>
        
        <p class="text-gray-500 mb-6">Gérez tous les partenaires du réseau de recharge et accédez à leurs informations détaillées.</p>
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bornes</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse(isset($partners) ? $partners : [] as $partner)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                                {{ substr($partner->name ?? 'Partner', 0, 1) }}
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $partner->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $partner->type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $partner->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $partner->phone }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $partner->stations_count }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $partner->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $partner->active ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('partners.show', $partner) ?? '#' }}" class="text-blue-500 hover:text-blue-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a href="{{ route('partners.edit', $partner) ?? '#' }}" class="text-indigo-500 hover:text-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form action="{{ route('partners.destroy', $partner) ?? '#' }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Êtes-vous sûr?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                @for ($i = 0; $i < 5; $i++)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                                {{ chr(65 + $i) }}
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ ['Morocco Mall', 'Carrefour Market', 'Total Energies', 'Marjane', 'Shell'][$i] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ['Centre commercial', 'Supermarché', 'Station-service', 'Hypermarché', 'Station-service'][$i] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ strtolower(str_replace(' ', '', ['Morocco Mall', 'Carrefour Market', 'Total Energies', 'Marjane', 'Shell'][$i])) }}@example.com</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">+212 5{{ rand(20, 28) }} {{ rand(100000, 999999) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rand(5, 25) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ rand(0, 4) > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ rand(0, 4) > 0 ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="#" class="text-blue-500 hover:text-blue-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a href="#" class="text-indigo-500 hover:text-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <button class="text-red-500 hover:text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endfor
                @endforelse
            </tbody>
        </table>
        
        <div class="flex justify-between items-center mt-6">
            <div class="text-sm text-gray-500">Page 1 of {{ isset($partners) && method_exists($partners, 'lastPage') ? $partners->lastPage() : 5 }}</div>
            <div class="flex space-x-2">
                @if(isset($partners) && method_exists($partners, 'links'))
                    {{ $partners->links() }}
                @else
                    <button class="h-8 w-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm">1</button>
                    <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">2</button>
                    <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">...</button>
                    <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">5</button>
                    <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">&gt;</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stats-card:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .stats-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    body {
        background-color: #f9fafb;
    }
</style>
@endpush