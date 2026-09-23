{{-- Page de démonstration du layout compact --}}
@extends('layouts.compact')

@section('title', 'Layout Compact - Démonstration')
@section('description', 'Démonstration du layout compact avec espacement réduit et responsivité optimisée')

@section('content')
<div class="compact-full-width">
    <!-- Grille responsive compacte -->
    <div class="compact-grid compact-grid-4 compact-gap-2 compact-mb-4">
        <!-- Carte 1 -->
        <div class="compact-card">
            <div class="compact-card-header">
                <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">Statistiques</h3>
            </div>
            <div class="compact-card-body">
                <div class="compact-flex compact-items-center compact-justify-between compact-mb-2">
                    <span class="compact-text-sm compact-text-gray-600">Points de charge</span>
                    <span class="compact-text-lg compact-font-bold compact-text-blue-600">24</span>
                </div>
                <div class="compact-flex compact-items-center compact-justify-between compact-mb-2">
                    <span class="compact-text-sm compact-text-gray-600">Transactions</span>
                    <span class="compact-text-lg compact-font-bold compact-text-green-600">156</span>
                </div>
                <div class="compact-flex compact-items-center compact-justify-between">
                    <span class="compact-text-sm compact-text-gray-600">Revenus</span>
                    <span class="compact-text-lg compact-font-bold compact-text-purple-600">€2,340</span>
                </div>
            </div>
        </div>

        <!-- Carte 2 -->
        <div class="compact-card">
            <div class="compact-card-header">
                <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">Actions rapides</h3>
            </div>
            <div class="compact-card-body">
                <div class="compact-flex compact-flex-col compact-gap-1">
                    <button class="compact-btn compact-btn-sm compact-bg-blue-600 compact-text-white hover:compact-bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nouveau Point
                    </button>
                    <button class="compact-btn compact-btn-sm compact-bg-green-600 compact-text-white hover:compact-bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Voir Rapports
                    </button>
                    <button class="compact-btn compact-btn-sm compact-bg-purple-600 compact-text-white hover:compact-bg-purple-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                        Paramètres
                    </button>
                </div>
            </div>
        </div>

        <!-- Carte 3 -->
        <div class="compact-card">
            <div class="compact-card-header">
                <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">Activité récente</h3>
            </div>
            <div class="compact-card-body">
                <div class="compact-space-y-1">
                    <div class="compact-flex compact-items-center compact-justify-between compact-p-1 compact-bg-gray-50 compact-rounded">
                        <span class="compact-text-xs compact-text-gray-600">Transaction #1234</span>
                        <span class="compact-text-xs compact-text-green-600">+€45.20</span>
                    </div>
                    <div class="compact-flex compact-items-center compact-justify-between compact-p-1 compact-bg-gray-50 compact-rounded">
                        <span class="compact-text-xs compact-text-gray-600">Nouveau point ajouté</span>
                        <span class="compact-text-xs compact-text-blue-600">Point #25</span>
                    </div>
                    <div class="compact-flex compact-items-center compact-justify-between compact-p-1 compact-bg-gray-50 compact-rounded">
                        <span class="compact-text-xs compact-text-gray-600">Maintenance terminée</span>
                        <span class="compact-text-xs compact-text-orange-600">Point #12</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte 4 -->
        <div class="compact-card">
            <div class="compact-card-header">
                <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">État du système</h3>
            </div>
            <div class="compact-card-body">
                <div class="compact-flex compact-items-center compact-justify-between compact-mb-1">
                    <span class="compact-text-sm compact-text-gray-600">Serveur</span>
                    <span class="compact-flex compact-items-center compact-gap-1">
                        <div class="w-2 h-2 compact-bg-green-500 compact-rounded-full"></div>
                        <span class="compact-text-xs compact-text-green-600">En ligne</span>
                    </span>
                </div>
                <div class="compact-flex compact-items-center compact-justify-between compact-mb-1">
                    <span class="compact-text-sm compact-text-gray-600">Base de données</span>
                    <span class="compact-flex compact-items-center compact-gap-1">
                        <div class="w-2 h-2 compact-bg-green-500 compact-rounded-full"></div>
                        <span class="compact-text-xs compact-text-green-600">Connectée</span>
                    </span>
                </div>
                <div class="compact-flex compact-items-center compact-justify-between">
                    <span class="compact-text-sm compact-text-gray-600">API</span>
                    <span class="compact-flex compact-items-center compact-gap-1">
                        <div class="w-2 h-2 compact-bg-yellow-500 compact-rounded-full"></div>
                        <span class="compact-text-xs compact-text-yellow-600">Lente</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau compact -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">Points de charge récents</h3>
        </div>
        <div class="compact-card-body compact-p-0">
            <div class="overflow-x-auto">
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th class="compact-text-xs">ID</th>
                            <th class="compact-text-xs">Nom</th>
                            <th class="compact-text-xs">Localisation</th>
                            <th class="compact-text-xs">Statut</th>
                            <th class="compact-text-xs">Puissance</th>
                            <th class="compact-text-xs">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="compact-text-xs">#001</td>
                            <td class="compact-text-xs">Station Centre</td>
                            <td class="compact-text-xs">Casablanca</td>
                            <td><span class="compact-text-xs compact-bg-green-100 compact-text-green-800 compact-p-1 compact-rounded">En ligne</span></td>
                            <td class="compact-text-xs">22 kW</td>
                            <td>
                                <button class="compact-btn compact-btn-sm compact-bg-blue-100 compact-text-blue-800 hover:compact-bg-blue-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="compact-text-xs">#002</td>
                            <td class="compact-text-xs">Station Nord</td>
                            <td class="compact-text-xs">Rabat</td>
                            <td><span class="compact-text-xs compact-bg-yellow-100 compact-text-yellow-800 compact-p-1 compact-rounded">Maintenance</span></td>
                            <td class="compact-text-xs">50 kW</td>
                            <td>
                                <button class="compact-btn compact-btn-sm compact-bg-blue-100 compact-text-blue-800 hover:compact-bg-blue-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="compact-text-xs">#003</td>
                            <td class="compact-text-xs">Station Sud</td>
                            <td class="compact-text-xs">Marrakech</td>
                            <td><span class="compact-text-xs compact-bg-red-100 compact-text-red-800 compact-p-1 compact-rounded">Hors ligne</span></td>
                            <td class="compact-text-xs">11 kW</td>
                            <td>
                                <button class="compact-btn compact-btn-sm compact-bg-blue-100 compact-text-blue-800 hover:compact-bg-blue-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulaire compact -->
    <div class="compact-card">
        <div class="compact-card-header">
            <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">Ajouter un point de charge</h3>
        </div>
        <div class="compact-card-body">
            <form class="compact-form">
                <div class="compact-grid compact-grid-2 compact-gap-2">
                    <div class="compact-form-group">
                        <label class="compact-form-label">Nom du point</label>
                        <input type="text" class="compact-form-input" placeholder="Ex: Station Centre">
                    </div>
                    <div class="compact-form-group">
                        <label class="compact-form-label">Localisation</label>
                        <input type="text" class="compact-form-input" placeholder="Ex: Casablanca">
                    </div>
                    <div class="compact-form-group">
                        <label class="compact-form-label">Puissance (kW)</label>
                        <input type="number" class="compact-form-input" placeholder="Ex: 22">
                    </div>
                    <div class="compact-form-group">
                        <label class="compact-form-label">Type de connecteur</label>
                        <select class="compact-form-input">
                            <option>Type 2</option>
                            <option>CCS</option>
                            <option>CHAdeMO</option>
                        </select>
                    </div>
                </div>
                <div class="compact-flex compact-justify-end compact-gap-2 compact-mt-2">
                    <button type="button" class="compact-btn compact-btn-sm compact-bg-gray-100 compact-text-gray-700 hover:compact-bg-gray-200">
                        Annuler
                    </button>
                    <button type="submit" class="compact-btn compact-btn-sm compact-bg-blue-600 compact-text-white hover:compact-bg-blue-700">
                        Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grille responsive démonstration -->
    <div class="compact-card compact-mt-4">
        <div class="compact-card-header">
            <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">Démonstration responsive</h3>
        </div>
        <div class="compact-card-body">
            <div class="compact-grid compact-grid-4 compact-gap-2">
                <div class="compact-bg-blue-100 compact-p-2 compact-rounded compact-text-sm compact-text-center">
                    <div class="compact-text-blue-800 compact-font-semibold">Colonne 1</div>
                    <div class="compact-text-xs compact-text-blue-600">Desktop: 25%</div>
                    <div class="compact-text-xs compact-text-blue-600">Mobile: 100%</div>
                </div>
                <div class="compact-bg-green-100 compact-p-2 compact-rounded compact-text-sm compact-text-center">
                    <div class="compact-text-green-800 compact-font-semibold">Colonne 2</div>
                    <div class="compact-text-xs compact-text-green-600">Desktop: 25%</div>
                    <div class="compact-text-xs compact-text-green-600">Mobile: 100%</div>
                </div>
                <div class="compact-bg-purple-100 compact-p-2 compact-rounded compact-text-sm compact-text-center">
                    <div class="compact-text-purple-800 compact-font-semibold">Colonne 3</div>
                    <div class="compact-text-xs compact-text-purple-600">Desktop: 25%</div>
                    <div class="compact-text-xs compact-text-purple-600">Mobile: 100%</div>
                </div>
                <div class="compact-bg-orange-100 compact-p-2 compact-rounded compact-text-sm compact-text-center">
                    <div class="compact-text-orange-800 compact-font-semibold">Colonne 4</div>
                    <div class="compact-text-xs compact-text-orange-600">Desktop: 25%</div>
                    <div class="compact-text-xs compact-text-orange-600">Mobile: 100%</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
