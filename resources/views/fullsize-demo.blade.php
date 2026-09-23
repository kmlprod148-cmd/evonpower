{{-- Page de démonstration du layout full-size --}}
@extends('layouts.app')

@section('title', 'Layout Full-Size - Démonstration')
@section('description', 'Démonstration du layout full-size avec contenu centré et responsive')

@section('header-actions')
<div class="flex gap-2">
    <button class="btn-fullsize btn-fullsize-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Nouveau
    </button>
    <button class="btn-fullsize btn-fullsize-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        Exporter
    </button>
</div>
@endsection

@section('content')
<div class="w-full">
    <!-- Statistiques full-size -->
    <div class="stats-fullsize grid-fullsize grid-fullsize-4 mb-6">
        <div class="stat-card-fullsize fullsize-transition fullsize-hover">
            <div class="stat-card-fullsize-header">
                <div>
                    <h3 class="stat-card-fullsize-title">Total des Points</h3>
                    <p class="stat-card-fullsize-value text-blue-600">156</p>
                </div>
                <div class="stat-card-fullsize-icon bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card-fullsize fullsize-transition fullsize-hover">
            <div class="stat-card-fullsize-header">
                <div>
                    <h3 class="stat-card-fullsize-title">Points Actifs</h3>
                    <p class="stat-card-fullsize-value text-green-600">142</p>
                </div>
                <div class="stat-card-fullsize-icon bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card-fullsize fullsize-transition fullsize-hover">
            <div class="stat-card-fullsize-header">
                <div>
                    <h3 class="stat-card-fullsize-title">En Maintenance</h3>
                    <p class="stat-card-fullsize-value text-yellow-600">8</p>
                </div>
                <div class="stat-card-fullsize-icon bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card-fullsize fullsize-transition fullsize-hover">
            <div class="stat-card-fullsize-header">
                <div>
                    <h3 class="stat-card-fullsize-title">Hors Ligne</h3>
                    <p class="stat-card-fullsize-value text-red-600">6</p>
                </div>
                <div class="stat-card-fullsize-icon bg-red-100 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation full-size -->
    <div class="nav-fullsize mb-6">
        <a href="#" class="nav-fullsize-item active">Tous</a>
        <a href="#" class="nav-fullsize-item">Actifs</a>
        <a href="#" class="nav-fullsize-item">Maintenance</a>
        <a href="#" class="nav-fullsize-item">Hors Ligne</a>
        <div class="ml-auto">
            <div class="flex gap-2">
                <button class="btn-fullsize btn-fullsize-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filtrer
                </button>
                <button class="btn-fullsize btn-fullsize-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Exporter
                </button>
            </div>
        </div>
    </div>

    <!-- Tableau full-size -->
    <div class="card-fullsize mb-6">
        <div class="card-fullsize-header">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Points de Charge</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Liste complète des points de charge</p>
        </div>
        <div class="card-fullsize-body">
            <div class="overflow-x-auto">
                <table class="table-fullsize">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Localisation</th>
                            <th>Statut</th>
                            <th>Puissance</th>
                            <th>Groupe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-mono text-sm">#001</td>
                            <td class="font-medium">Station Centre</td>
                            <td>Casablanca, Maroc</td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <div class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></div>
                                    En ligne
                                </span>
                            </td>
                            <td class="font-mono">22 kW</td>
                            <td>Groupe A</td>
                            <td>
                                <div class="flex gap-1">
                                    <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-fullsize btn-fullsize-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-mono text-sm">#002</td>
                            <td class="font-medium">Station Nord</td>
                            <td>Rabat, Maroc</td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <div class="w-1.5 h-1.5 bg-yellow-400 rounded-full mr-1.5"></div>
                                    Maintenance
                                </span>
                            </td>
                            <td class="font-mono">50 kW</td>
                            <td>Groupe B</td>
                            <td>
                                <div class="flex gap-1">
                                    <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-fullsize btn-fullsize-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-mono text-sm">#003</td>
                            <td class="font-medium">Station Sud</td>
                            <td>Marrakech, Maroc</td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <div class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></div>
                                    Hors ligne
                                </span>
                            </td>
                            <td class="font-mono">11 kW</td>
                            <td>Groupe C</td>
                            <td>
                                <div class="flex gap-1">
                                    <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-fullsize btn-fullsize-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulaire full-size -->
    <div class="card-fullsize">
        <div class="card-fullsize-header">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ajouter un Point de Charge</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Remplissez les informations pour créer un nouveau point</p>
        </div>
        <div class="card-fullsize-body">
            <form class="form-fullsize">
                <div class="grid-fullsize grid-fullsize-2 gap-6">
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Nom du Point</label>
                        <input type="text" class="form-fullsize-input" placeholder="Ex: Station Centre">
                    </div>
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Localisation</label>
                        <input type="text" class="form-fullsize-input" placeholder="Ex: Casablanca, Maroc">
                    </div>
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Puissance (kW)</label>
                        <input type="number" class="form-fullsize-input" placeholder="Ex: 22">
                    </div>
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Type de Connecteur</label>
                        <select class="form-fullsize-input">
                            <option>Type 2</option>
                            <option>CCS</option>
                            <option>CHAdeMO</option>
                        </select>
                    </div>
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Groupe</label>
                        <select class="form-fullsize-input">
                            <option>Groupe A</option>
                            <option>Groupe B</option>
                            <option>Groupe C</option>
                        </select>
                    </div>
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Statut</label>
                        <select class="form-fullsize-input">
                            <option>En ligne</option>
                            <option>Hors ligne</option>
                            <option>Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="form-fullsize-group">
                    <label class="form-fullsize-label">Description</label>
                    <textarea class="form-fullsize-input" rows="3" placeholder="Description du point de charge..."></textarea>
                </div>
                <div class="card-fullsize-footer">
                    <div class="flex justify-end gap-3">
                        <button type="button" class="btn-fullsize btn-fullsize-secondary">
                            Annuler
                        </button>
                        <button type="submit" class="btn-fullsize btn-fullsize-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Créer le Point
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Démonstration responsive -->
    <div class="card-fullsize mt-6">
        <div class="card-fullsize-header">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Démonstration Responsive</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Grilles qui s'adaptent automatiquement à la taille de l'écran</p>
        </div>
        <div class="card-fullsize-body">
            <div class="grid-fullsize grid-fullsize-6 gap-4">
                <div class="bg-blue-100 p-4 rounded-lg text-center text-blue-800 font-semibold">
                    <div class="text-sm">Desktop: 16.67%</div>
                    <div class="text-xs">Mobile: 100%</div>
                </div>
                <div class="bg-green-100 p-4 rounded-lg text-center text-green-800 font-semibold">
                    <div class="text-sm">Desktop: 16.67%</div>
                    <div class="text-xs">Mobile: 100%</div>
                </div>
                <div class="bg-purple-100 p-4 rounded-lg text-center text-purple-800 font-semibold">
                    <div class="text-sm">Desktop: 16.67%</div>
                    <div class="text-xs">Mobile: 100%</div>
                </div>
                <div class="bg-orange-100 p-4 rounded-lg text-center text-orange-800 font-semibold">
                    <div class="text-sm">Desktop: 16.67%</div>
                    <div class="text-xs">Mobile: 100%</div>
                </div>
                <div class="bg-red-100 p-4 rounded-lg text-center text-red-800 font-semibold">
                    <div class="text-sm">Desktop: 16.67%</div>
                    <div class="text-xs">Mobile: 100%</div>
                </div>
                <div class="bg-yellow-100 p-4 rounded-lg text-center text-yellow-800 font-semibold">
                    <div class="text-sm">Desktop: 16.67%</div>
                    <div class="text-xs">Mobile: 100%</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
