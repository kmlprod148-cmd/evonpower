@extends('layouts.app')

@section('title', 'Gestion des Groupes')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <!-- En-tête avec bouton d'ajout -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Gestion des Groupes</h2>
            <p class="mt-1 text-sm text-gray-500">Gérez les groupes de bornes et d'utilisateurs.</p>
        </div>
        
        <a href="{{ route('groups.create.step1') }}" class="inline-flex items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-300 disabled:opacity-25 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Ajouter un groupe
        </a>
    </div>

    <!-- Messages de notification -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <!-- Barre de recherche et filtres -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex-grow max-w-md">
            <div class="relative">
                <input id="searchInput" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Rechercher un groupe..." type="search">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="flex items-center space-x-2">
            <div>
                <select id="statusFilter" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
            </div>
            
            <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filtres avancés
            </button>
        </div>
    </div>

    <!-- Tableau des groupes -->
    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nom du groupe
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Adresse
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ville
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date de création
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($groups as $group)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $group->name ?? $group->title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $group->type }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $group->address }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $group->city }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $group->created_at ? $group->created_at->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex space-x-3 justify-end">
                            <a href="{{ route('groups.show', $group->id) }}" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 transition ease-in-out duration-150">
                                View Details
                            </a>
                            <a href="{{ route('groups.edit', $group->id) }}" class="text-green-500 hover:text-green-700" title="Modifier">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            
                            <form action="{{ route('groups.destroy', $group->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Supprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun groupe trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500">Commencez par créer un nouveau groupe.</p>
                            <div class="mt-6">
                                <a href="{{ route('groups.create.step1') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Créer un groupe
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Drawer pour les filtres avancés (caché par défaut) -->
    <div id="filtersDrawer" class="fixed inset-y-0 right-0 transform translate-x-full transition duration-300 ease-in-out z-20 w-80 bg-white shadow-lg overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900">Filtres avancés</h3>
                <button id="closeFiltersDrawer" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="filtersForm" class="space-y-6">
                <div>
                    <label for="typeFilter" class="block text-sm font-medium text-gray-700">Type de groupe</label>
                    <select id="typeFilter" name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                        <option value="">Tous les types</option>
                        <option value="enterprise">Entreprise</option>
                        <option value="residential">Résidentiel</option>
                        <option value="public">Public</option>
                    </select>
                </div>
                
                <div>
                    <label for="cityFilter" class="block text-sm font-medium text-gray-700">Ville</label>
                    <select id="cityFilter" name="city" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                        <option value="">Toutes les villes</option>
                        <option value="Casablanca">Casablanca</option>
                        <option value="Rabat">Rabat</option>
                        <option value="Marrakech">Marrakech</option>
                        <option value="Tanger">Tanger</option>
                    </select>
                </div>
                
                <div>
                    <label for="dateRangeFilter" class="block text-sm font-medium text-gray-700">Période de création</label>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <div>
                            <label for="dateFrom" class="sr-only">Date de début</label>
                            <input type="date" id="dateFrom" name="date_from" class="block w-full shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label for="dateTo" class="sr-only">Date de fin</label>
                            <input type="date" id="dateTo" name="date_to" class="block w-full shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <button type="button" id="resetFilters" class="text-sm font-medium text-gray-700 hover:text-gray-500">
                        Réinitialiser les filtres
                    </button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression (caché par défaut) -->
    <div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Confirmer la suppression
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Êtes-vous sûr de vouloir supprimer ce groupe? Cette action est irréversible et toutes les données associées seront également supprimées.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmDelete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Supprimer
                    </button>
                    <button type="button" id="cancelDelete" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    </div>


<!-- Vue pour l'impression uniquement -->
<style media="print">
    @page {
        size: A4;
        margin: 1cm;
    }
    body {
        font-family: Arial, sans-serif;
        font-size: 12pt;
    }
    .no-print {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    table th, table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    table th {
        background-color: #f2f2f2;
    }
    .print-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .print-footer {
        text-align: center;
        margin-top: 20px;
        font-size: 10pt;
        color: #666;
    }
</style>

<!-- Contenu visible uniquement à l'impression -->
<div class="print-only hidden">
    <div class="print-header">
        <h1>Liste des Groupes</h1>
        <p>Exporté le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="print-footer">
        <p>© {{ date('Y') }} EVON - Système de gestion des bornes de recharge</p>
    </div>
</div>

<!-- Drawer pour les filtres avancés (caché par défaut) -->
<div id="filtersDrawer" class="fixed inset-y-0 right-0 transform translate-x-full transition duration-300 ease-in-out z-20 w-80 bg-white shadow-lg overflow-y-auto">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">Filtres avancés</h3>
            <button id="closeFiltersDrawer" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="filtersForm" class="space-y-6">
            <div>
                <label for="typeFilter" class="block text-sm font-medium text-gray-700">Type de groupe</label>
                <select id="typeFilter" name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                    <option value="">Tous les types</option>
                    <option value="enterprise">Entreprise</option>
                    <option value="residential">Résidentiel</option>
                    <option value="public">Public</option>
                </select>
            </div>
            
            <div>
                <label for="cityFilter" class="block text-sm font-medium text-gray-700">Ville</label>
                <select id="cityFilter" name="city" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                    <option value="">Toutes les villes</option>
                    <option value="Casablanca">Casablanca</option>
                    <option value="Rabat">Rabat</option>
                    <option value="Marrakech">Marrakech</option>
                    <option value="Tanger">Tanger</option>
                </select>
            </div>
            
            <div>
                <label for="dateRangeFilter" class="block text-sm font-medium text-gray-700">Période de création</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <div>
                        <label for="dateFrom" class="sr-only">Date de début</label>
                        <input type="date" id="dateFrom" name="date_from" class="block w-full shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="dateTo" class="sr-only">Date de fin</label>
                        <input type="date" id="dateTo" name="date_to" class="block w-full shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <button type="button" id="resetFilters" class="text-sm font-medium text-gray-700 hover:text-gray-500">
                    Réinitialiser les filtres
                </button>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Appliquer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation de suppression (caché par défaut) -->
<div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Confirmer la suppression
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Êtes-vous sûr de vouloir supprimer ce groupe? Cette action est irréversible et toutes les données associées seront également supprimées.
                            </p>
                            <p class="mt-2 text-sm text-red-500">
                                ATTENTION: Cette action supprimera également toutes les bornes et utilisateurs associés à ce groupe.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmDelete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Supprimer
                </button>
                <button type="button" id="cancelDelete" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
   document.addEventListener('DOMContentLoaded', function() {
       // Variables pour la modal de confirmation
       const deleteModal = document.getElementById('deleteModal');
       const confirmDeleteBtn = document.getElementById('confirmDelete');
       const cancelDeleteBtn = document.getElementById('cancelDelete');
       let currentForm = null;

       // Ouvrir la modal de confirmation
       document.querySelectorAll('.delete-form').forEach(form => {
           form.addEventListener('submit', function(e) {
               e.preventDefault();
               currentForm = this;
               deleteModal.classList.remove('hidden');
           });
       });

       // Fermer la modal et annuler la suppression
       cancelDeleteBtn.addEventListener('click', function() {
           deleteModal.classList.add('hidden');
           currentForm = null;
       });

      // Confirmer la suppression
      confirmDeleteBtn.addEventListener('click', function() {
           if (currentForm) {
               currentForm.submit();
           }
           deleteModal.classList.add('hidden');
       });

       // Recherche en temps réel
       const searchInput = document.getElementById('searchInput');
       if (searchInput) {
           searchInput.addEventListener('input', function() {
               const searchTerm = this.value.toLowerCase();
               
               document.querySelectorAll('tbody tr').forEach(row => {
                   if (!row.querySelector('td[colspan]')) {  // Ignorer la ligne "aucun résultat"
                       const name = row.querySelector('td:first-child').textContent.toLowerCase();
                       const type = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                       const address = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                       const city = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                       
                       if (name.includes(searchTerm) || 
                           type.includes(searchTerm) || 
                           address.includes(searchTerm) || 
                           city.includes(searchTerm)) {
                           row.classList.remove('hidden');
                       } else {
                           row.classList.add('hidden');
                       }
                   }
               });
               
               // Vérifier s'il y a des résultats visibles
               updateNoResultsMessage();
           });
       }

       // Filtrage par statut
       const statusFilter = document.getElementById('statusFilter');
       if (statusFilter) {
           statusFilter.addEventListener('change', function() {
               const filterValue = this.value;
               
               document.querySelectorAll('tbody tr').forEach(row => {
                   if (!row.querySelector('td[colspan]')) {
                       if (filterValue === '') {
                           row.classList.remove('hidden');
                       } else {
                           const statusElement = row.querySelector('td:nth-child(4) span');
                           if (statusElement) {
                               const status = statusElement.textContent.trim().toLowerCase();
                               if ((filterValue === 'active' && status === 'actif') || 
                                   (filterValue === 'inactive' && status === 'inactif')) {
                                   row.classList.remove('hidden');
                               } else {
                                   row.classList.add('hidden');
                               }
                           }
                       }
                   }
               });

               // Mise à jour du message "Aucun résultat" après filtrage
               updateNoResultsMessage();
           });
       }
       
       // Drawer des filtres avancés
       const filtersButton = document.querySelector('button[title="Filtres avancés"]');
       const filtersDrawer = document.getElementById('filtersDrawer');
       const closeFiltersDrawerBtn = document.getElementById('closeFiltersDrawer');
       const filtersForm = document.getElementById('filtersForm');
       const resetFiltersBtn = document.getElementById('resetFilters');
       
       if (filtersButton && filtersDrawer) {
           // Ouvrir le drawer
           filtersButton.addEventListener('click', function() {
               filtersDrawer.classList.remove('translate-x-full');
           });
           
           // Fermer le drawer
           closeFiltersDrawerBtn.addEventListener('click', function() {
               filtersDrawer.classList.add('translate-x-full');
           });
           
           // Clic en dehors du drawer pour fermer
           document.addEventListener('click', function(e) {
               if (filtersDrawer && !filtersDrawer.contains(e.target) && e.target !== filtersButton) {
                   filtersDrawer.classList.add('translate-x-full');
               }
           });
           
           // Soumettre le formulaire de filtres
           if (filtersForm) {
               filtersForm.addEventListener('submit', function(e) {
                   e.preventDefault();
                   
                   // Récupérer les valeurs des filtres
                   const typeFilter = document.getElementById('typeFilter').value;
                   const cityFilter = document.getElementById('cityFilter').value;
                   const dateFrom = document.getElementById('dateFrom').value;
                   const dateTo = document.getElementById('dateTo').value;
                   
                   // Appliquer les filtres
                   document.querySelectorAll('tbody tr').forEach(row => {
                       if (!row.querySelector('td[colspan]')) {
                           let showRow = true;
                           
                           // Filtrer par type
                           if (typeFilter && typeFilter !== '') {
                               const rowType = row.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
                               if (!rowType.includes(typeFilter.toLowerCase())) {
                                   showRow = false;
                               }
                           }
                           
                           // Filtrer par ville
                           if (showRow && cityFilter && cityFilter !== '') {
                               const rowCity = row.querySelector('td:nth-child(4)').textContent.trim().toLowerCase();
                               if (!rowCity.includes(cityFilter.toLowerCase())) {
                                   showRow = false;
                               }
                           }
                           
                           // Filtrer par date
                           if (showRow && (dateFrom || dateTo)) {
                               const dateString = row.querySelector('td:nth-child(5)').textContent.trim();
                               const dateParts = dateString.split('/');
                               const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
                               
                               if (dateFrom && new Date(dateFrom) > rowDate) {
                                   showRow = false;
                               }
                               
                               if (dateTo && new Date(dateTo) < rowDate) {
                                   showRow = false;
                               }
                           }
                           
                           if (showRow) {
                               row.classList.remove('hidden');
                           } else {
                               row.classList.add('hidden');
                           }
                       }
                   });
                   
                   // Fermer le drawer
                   filtersDrawer.classList.add('translate-x-full');
                   
                   // Mise à jour du message "Aucun résultat" après filtrage
                   updateNoResultsMessage();
                   
                   // Indicateur visuel des filtres actifs
                   if (typeFilter || cityFilter || dateFrom || dateTo) {
                       filtersButton.classList.add('bg-green-600');
                   } else {
                       filtersButton.classList.remove('bg-green-600');
                   }
               });
           }
           
           // Réinitialiser les filtres
           if (resetFiltersBtn) {
               resetFiltersBtn.addEventListener('click', function() {
                   document.getElementById('typeFilter').value = '';
                   document.getElementById('cityFilter').value = '';
                   document.getElementById('dateFrom').value = '';
                   document.getElementById('dateTo').value = '';
                   
                   // Réinitialiser l'affichage
                   document.querySelectorAll('tbody tr').forEach(row => {
                       if (!row.querySelector('td[colspan]')) {
                           row.classList.remove('hidden');
                       }
                   });
                   
                   // Supprimer l'indicateur visuel des filtres actifs
                   filtersButton.classList.remove('bg-green-600');
                   
                   // Mise à jour du message "Aucun résultat"
                   const noResultsRow = document.querySelector('#no-results-row');
                   if (noResultsRow) {
                       noResultsRow.remove();
                   }
               });
           }
       }
       
       // Bouton d'impression
       const printButton = document.querySelector('button[title="Imprimer"]');
       if (printButton) {
           printButton.addEventListener('click', function() {
               window.print();
           });
       }
       
       // Bouton d'exportation
       const exportButton = document.querySelector('button[title="Exporter"]');
       if (exportButton) {
           exportButton.addEventListener('click', function() {
               // Créer un tableau de données
               const data = [];
               const headers = ['Nom', 'Type', 'Adresse', 'Ville', 'Date de création'];
               data.push(headers);
               
               document.querySelectorAll('tbody tr').forEach(row => {
                   if (!row.querySelector('td[colspan]') && !row.classList.contains('hidden')) {
                       const rowData = [];
                       rowData.push(row.querySelector('td:nth-child(1)').textContent.trim());
                       rowData.push(row.querySelector('td:nth-child(2)').textContent.trim());
                       rowData.push(row.querySelector('td:nth-child(3)').textContent.trim());
                       rowData.push(row.querySelector('td:nth-child(4)').textContent.trim());
                       rowData.push(row.querySelector('td:nth-child(5)').textContent.trim());
                       data.push(rowData);
                   }
               });
               
               // Créer un CSV
               const csvContent = "data:text/csv;charset=utf-8," + 
                   data.map(e => e.join(",")).join("\n");
               
               // Créer un lien de téléchargement
               const encodedUri = encodeURI(csvContent);
               const link = document.createElement("a");
               link.setAttribute("href", encodedUri);
               link.setAttribute("download", "groupes_export_" + new Date().toISOString().split('T')[0] + ".csv");
               document.body.appendChild(link);
               
               // Télécharger le fichier
               link.click();
               
               // Nettoyer le DOM
               document.body.removeChild(link);
           });
       }
       
       // Bouton de paramètres
       const settingsButton = document.querySelector('button[title="Paramètres"]');
       if (settingsButton) {
           settingsButton.addEventListener('click', function() {
               // Implémenter un menu déroulant ou une modal avec options
               alert('Options de paramètres: \n- Configurer l\'affichage\n- Préférences utilisateur\n- Options d\'export');
           });
       }
       
       // Bouton d'aide
       const helpButton = document.querySelector('button[title="Aide"]');
       if (helpButton) {
           helpButton.addEventListener('click', function() {
               // Afficher une modal d'aide ou rediriger vers une page d'aide
               alert('Aide sur la gestion des groupes: \n\n1. Pour ajouter un groupe, cliquez sur le bouton "Ajouter un groupe"\n2. Pour modifier un groupe, cliquez sur l\'icône de modification\n3. Pour supprimer un groupe, cliquez sur l\'icône de suppression\n4. Utilisez les filtres pour affiner votre recherche');
           });
       }
       
       // Bouton d'actualisation
       const refreshButton = document.querySelector('button[title="Actualiser"]');
       if (refreshButton) {
           refreshButton.addEventListener('click', function() {
               location.reload();
           });
       }

       // Fonction pour mettre à jour le message "Aucun résultat"
       function updateNoResultsMessage() {
           const hasVisibleRows = Array.from(document.querySelectorAll('tbody tr'))
               .some(row => !row.classList.contains('hidden') && !row.querySelector('td[colspan]'));
           
           const noResultsRow = document.querySelector('#no-results-row');
           if (!hasVisibleRows && !noResultsRow) {
               const tbody = document.querySelector('tbody');
               const newRow = document.createElement('tr');
               newRow.id = 'no-results-row';
               newRow.innerHTML = `
                   <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                       Aucun groupe ne correspond aux critères sélectionnés.
                   </td>
               `;
               tbody.appendChild(newRow);
           } else if (hasVisibleRows && noResultsRow) {
               noResultsRow.remove();
           }
       }
   });
</script>
@endpush

@endsection