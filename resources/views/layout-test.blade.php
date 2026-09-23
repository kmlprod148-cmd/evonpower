{{-- Page de test du layout corrigé --}}
@extends('layouts.app')

@section('title', 'Test Layout - EVON')
@section('description', 'Test du layout corrigé avec sidebar et contenu full-size')

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
    <!-- Test du layout full-size -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test du Layout Corrigé</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste le layout corrigé avec sidebar et contenu full-size responsive.
        </p>
        
        <!-- Test des grilles responsives -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800 dark:text-blue-200">Test 1</h3>
                <p class="text-blue-600 dark:text-blue-300">Contenu de test</p>
            </div>
            <div class="bg-green-100 dark:bg-green-900 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800 dark:text-green-200">Test 2</h3>
                <p class="text-green-600 dark:text-green-300">Contenu de test</p>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-lg">
                <h3 class="font-semibold text-purple-800 dark:text-purple-200">Test 3</h3>
                <p class="text-purple-600 dark:text-purple-300">Contenu de test</p>
            </div>
        </div>

        <!-- Test des boutons -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button class="btn-fullsize btn-fullsize-primary">Bouton Primaire</button>
            <button class="btn-fullsize btn-fullsize-secondary">Bouton Secondaire</button>
            <button class="btn-fullsize btn-fullsize-success">Bouton Succès</button>
            <button class="btn-fullsize btn-fullsize-danger">Bouton Danger</button>
        </div>

        <!-- Test du tableau -->
        <div class="overflow-x-auto">
            <table class="table-fullsize">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-mono text-sm">#001</td>
                        <td class="font-medium">Test Item 1</td>
                        <td>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Actif
                            </span>
                        </td>
                        <td>
                            <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                Modifier
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-mono text-sm">#002</td>
                        <td class="font-medium">Test Item 2</td>
                        <td>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                En attente
                            </span>
                        </td>
                        <td>
                            <button class="btn-fullsize btn-fullsize-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                Modifier
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Test des statistiques -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card-fullsize fullsize-transition fullsize-hover">
            <div class="stat-card-fullsize-header">
                <div>
                    <h3 class="stat-card-fullsize-title">Total</h3>
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
                    <h3 class="stat-card-fullsize-title">Actifs</h3>
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
                    <h3 class="stat-card-fullsize-title">En attente</h3>
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
                    <h3 class="stat-card-fullsize-title">Erreurs</h3>
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

    <!-- Test du formulaire -->
    <div class="card-fullsize">
        <div class="card-fullsize-header">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Test de Formulaire</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Formulaire de test avec layout full-size</p>
        </div>
        <div class="card-fullsize-body">
            <form class="form-fullsize">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Nom</label>
                        <input type="text" class="form-fullsize-input" placeholder="Votre nom">
                    </div>
                    <div class="form-fullsize-group">
                        <label class="form-fullsize-label">Email</label>
                        <input type="email" class="form-fullsize-input" placeholder="votre@email.com">
                    </div>
                </div>
                <div class="form-fullsize-group mb-4">
                    <label class="form-fullsize-label">Message</label>
                    <textarea class="form-fullsize-input" rows="3" placeholder="Votre message..."></textarea>
                </div>
                <div class="card-fullsize-footer">
                    <div class="flex justify-end gap-3">
                        <button type="button" class="btn-fullsize btn-fullsize-secondary">
                            Annuler
                        </button>
                        <button type="submit" class="btn-fullsize btn-fullsize-primary">
                            Envoyer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
