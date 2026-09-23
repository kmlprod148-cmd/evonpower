{{-- Page de test du contenu centré --}}
@extends('layouts.app')

@section('title', 'Test Contenu Centré - EVON')
@section('description', 'Test du contenu principal centré avec largeur maximale')

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
    <!-- Test du contenu centré -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test du Contenu Centré</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste le contenu principal centré avec une largeur maximale de 7xl (1280px).
        </p>
        
        <!-- Indicateur de largeur -->
        <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-blue-800 dark:text-blue-200 font-semibold">
                    Contenu centré avec max-width: 7xl (1280px)
                </span>
            </div>
        </div>

        <!-- Grille de test -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-green-100 dark:bg-green-900 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800 dark:text-green-200 mb-2">Carte 1</h3>
                <p class="text-green-600 dark:text-green-300 text-sm">Contenu de test pour vérifier le centrage</p>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-lg">
                <h3 class="font-semibold text-purple-800 dark:text-purple-200 mb-2">Carte 2</h3>
                <p class="text-purple-600 dark:text-purple-300 text-sm">Contenu de test pour vérifier le centrage</p>
            </div>
            <div class="bg-orange-100 dark:bg-orange-900 p-4 rounded-lg">
                <h3 class="font-semibold text-orange-800 dark:text-orange-200 mb-2">Carte 3</h3>
                <p class="text-orange-600 dark:text-orange-300 text-sm">Contenu de test pour vérifier le centrage</p>
            </div>
        </div>

        <!-- Tableau de test -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">Test Item 1</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Actif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <button class="text-indigo-600 hover:text-indigo-900">Modifier</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">Test Item 2</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                En attente
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <button class="text-indigo-600 hover:text-indigo-900">Modifier</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Formulaire de test -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Formulaire de Test</h3>
            <form class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" placeholder="Votre nom">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" placeholder="votre@email.com">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" rows="3" placeholder="Votre message..."></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Test de largeur maximale -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
        <h3 class="text-xl font-bold mb-2">Test de Largeur Maximale</h3>
        <p class="mb-4">Cette section teste la largeur maximale du contenu centré.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <h4 class="font-semibold mb-2">Colonne 1</h4>
                <p class="text-sm">Contenu de test</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <h4 class="font-semibold mb-2">Colonne 2</h4>
                <p class="text-sm">Contenu de test</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <h4 class="font-semibold mb-2">Colonne 3</h4>
                <p class="text-sm">Contenu de test</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <h4 class="font-semibold mb-2">Colonne 4</h4>
                <p class="text-sm">Contenu de test</p>
            </div>
        </div>
    </div>
</div>
@endsection
