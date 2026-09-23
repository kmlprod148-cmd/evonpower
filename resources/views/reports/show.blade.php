@extends('layouts.app')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">{{ $report->title ?? 'Détails du rapport' }}</h1>
        
        <div class="flex space-x-3">
            <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour à la liste
            </a>
            
            <a href="{{ route('reports.edit', $report->id ?? 1) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Modifier
            </a>
            
            <button onclick="document.getElementById('exportReport').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Exporter
            </button>
        </div>
    </div>
    
    <!-- Report Info -->
    <div class="bg-gray-50 p-6 rounded-lg mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider">Informations générales</h3>
                <div class="mt-2 space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Type</p>
                        <p class="text-sm text-gray-900">
                            @php
                                $typeLabels = [
                                    'usage' => 'Utilisation',
                                    'financial' => 'Financier',
                                    'performance' => 'Performance',
                                    'custom' => 'Personnalisé'
                                ];
                            @endphp
                            {{ $typeLabels[$report->type ?? 'custom'] ?? 'Personnalisé' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Description</p>
                        <p class="text-sm text-gray-900">{{ $report->description ?? 'Aucune description disponible' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Statut</p>
                        @php
                            $statusLabels = [
                                'draft' => 'Brouillon',
                                'scheduled' => 'Programmé',
                                'published' => 'Publié',
                                'archived' => 'Archivé'
                            ];
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($report->status ?? 'draft') == 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$report->status ?? 'draft'] ?? 'Brouillon' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider">Période</h3>
                <div class="mt-2 space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date de début</p>
                        <p class="text-sm text-gray-900">{{ $report->start_date ? \Carbon\Carbon::parse($report->start_date)->format('d/m/Y') : 'Non définie' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date de fin</p>
                        <p class="text-sm text-gray-900">{{ $report->end_date ? \Carbon\Carbon::parse($report->end_date)->format('d/m/Y') : 'Non définie' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Créé par</p>
                        <p class="text-sm text-gray-900">{{ $report->user->name ?? 'Administrateur' }}</p>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider">Options</h3>
                <div class="mt-2 space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Partenaire</p>
                        <p class="text-sm text-gray-900">{{ $report->partner->name ?? 'Tous les partenaires' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Destinataires</p>
                        <p class="text-sm text-gray-900">{{ $report->recipients ?? 'Aucun' }}</p>
                    </div>
                    <div class="flex flex-col space-y-1">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ ($report->include_charts ?? false) ? 'text-green-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="ml-2 text-sm text-gray-700">Inclure les graphiques</span>
                        </div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ ($report->include_summary ?? false) ? 'text-green-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="ml-2 text-sm text-gray-700">Inclure un résumé</span>
                        </div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ ($report->auto_send ?? false) ? 'text-green-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="ml-2 text-sm text-gray-700">Envoi automatique</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report Content Placeholder -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Contenu du rapport</h2>
        
        <!-- Graph Placeholders -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 rounded-lg p-4 h-64 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 h-64 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
            </div>
        </div>
        
        <!-- Table Placeholder -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisation (kWh)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenus</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points actifs</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @for ($i = 0; $i < 5; $i++)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::now()->subDays($i)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rand(100, 500) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rand(20, 100) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format(rand(500, 2000), 2) }} €</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rand(10, 30) }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportReport" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black opacity-50"></div>
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full relative z-10">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Exporter le rapport</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('exportReport').classList.add('hidden')">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label for="export_format" class="block text-sm font-medium text-gray-700">Format</label>
                <select id="export_format" name="export_format" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                    <option value="csv">CSV</option>
                </select>
            </div>
            
            <div>
                <label for="export_period" class="block text-sm font-medium text-gray-700">Période</label>
                <select id="export_period" name="export_period" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                    <option value="default">Période définie dans le rapport</option>
                    <option value="month">Dernier mois</option>
                    <option value="quarter">Dernier trimestre</option>
                    <option value="year">Dernière année</option>
                    <option value="custom">Personnalisée</option>
                </select>
            </div>
            
            <div class="flex items-center">
                <input id="include_graphics" name="include_graphics" type="checkbox" checked class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                <label for="include_graphics" class="ml-2 block text-sm text-gray-700">Inclure les graphiques</label>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="button" onclick="document.getElementById('exportReport').classList.add('hidden')" class="mr-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Annuler
            </button>
            <button type="button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Télécharger
            </button>
        </div>
    </div>
</div>

@endsection