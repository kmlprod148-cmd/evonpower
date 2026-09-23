{{-- Page de test du layout amélioré --}}
@extends('layouts.app-enhanced')

@section('title', 'Test Layout Amélioré - EVON')
@section('description', 'Test du layout amélioré avec sidebar moderne et design optimisé')

@section('content')
<div class="w-full">
    <!-- Test du layout amélioré -->
    <div class="card-enhanced p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test du Layout Amélioré</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste le layout amélioré avec sidebar moderne, design optimisé et animations fluides.
        </p>
        
        <!-- Indicateur d'état -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold">
                    ✅ Layout Amélioré Actif | 
                    Sidebar: <span id="sidebar-status">Étendue</span> | 
                    Thème: <span id="theme-status">Clair</span> | 
                    Largeur: <span id="screen-width">-</span>px
                </span>
            </div>
        </div>

        <!-- Fonctionnalités testées -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="card-enhanced p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Design Moderne</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Sidebar avec gradient élégant</li>
                    <li>• Animations fluides et transitions</li>
                    <li>• Cards avec hover effects</li>
                    <li>• Design responsive parfait</li>
                </ul>
            </div>
            <div class="card-enhanced p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Navigation Intelligente</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Sections collapsibles</li>
                    <li>• Détection automatique d'état</li>
                    <li>• Hover effects avancés</li>
                    <li>• Badges informatifs</li>
                </ul>
            </div>
            <div class="card-enhanced p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Performance</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• CSS optimisé</li>
                    <li>• Animations GPU-accelerated</li>
                    <li>• Chargement rapide</li>
                    <li>• Responsive fluide</li>
                </ul>
            </div>
        </div>

        <!-- Test des composants -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="card-enhanced p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test des Boutons</h3>
                <div class="space-y-3">
                    <button class="btn-enhanced btn-enhanced-primary w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Bouton Principal
                    </button>
                    <button class="btn-enhanced btn-enhanced-secondary w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                        Bouton Secondaire
                    </button>
                </div>
            </div>

            <div class="card-enhanced p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test des Animations</h3>
                <div class="space-y-3">
                    <button onclick="testFadeIn()" class="btn-enhanced btn-enhanced-primary w-full">
                        Test Fade In
                    </button>
                    <button onclick="testSlideIn()" class="btn-enhanced btn-enhanced-secondary w-full">
                        Test Slide In
                    </button>
                    <button onclick="testLoading()" class="btn-enhanced btn-enhanced-primary w-full">
                        Test Loading
                    </button>
                </div>
            </div>
        </div>

        <!-- Test de la grille -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card-enhanced p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Énergie</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Gestion intelligente</p>
            </div>
            <div class="card-enhanced p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Analytics</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Données en temps réel</p>
            </div>
            <div class="card-enhanced p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Utilisateurs</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Gestion des comptes</p>
            </div>
            <div class="card-enhanced p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Paramètres</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Configuration avancée</p>
            </div>
        </div>

        <!-- Instructions de test -->
        <div class="card-enhanced p-6 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20">
            <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Instructions de Test</h3>
            <ul class="text-gray-700 dark:text-gray-300 text-sm space-y-1">
                <li>• <strong>Toggle Sidebar</strong> : Testez le collapse/expand de la sidebar</li>
                <li>• <strong>Toggle Theme</strong> : Basculez entre mode clair et sombre</li>
                <li>• <strong>Hover Effects</strong> : Survolez les cards et boutons</li>
                <li>• <strong>Responsive</strong> : Testez sur différentes tailles d'écran</li>
                <li>• <strong>Animations</strong> : Cliquez sur les boutons de test</li>
            </ul>
        </div>
    </div>
</div>

<script>
function testFadeIn() {
    const element = document.querySelector('.card-enhanced');
    if (element) {
        element.classList.remove('fade-in');
        setTimeout(() => {
            element.classList.add('fade-in');
        }, 100);
    }
}

function testSlideIn() {
    const element = document.querySelector('.card-enhanced');
    if (element) {
        element.classList.remove('slide-in');
        setTimeout(() => {
            element.classList.add('slide-in');
        }, 100);
    }
}

function testLoading() {
    const element = document.querySelector('.card-enhanced');
    if (element) {
        element.classList.add('loading-enhanced');
        setTimeout(() => {
            element.classList.remove('loading-enhanced');
        }, 2000);
    }
}

function updateStatus() {
    // Update sidebar status
    const sidebarStatus = document.getElementById('sidebar-status');
    const themeStatus = document.getElementById('theme-status');
    const screenWidth = document.getElementById('screen-width');
    
    // Get current window width
    screenWidth.textContent = window.innerWidth;
    
    // Get theme status
    if (document.documentElement.classList.contains('dark')) {
        themeStatus.textContent = 'Sombre';
    } else {
        themeStatus.textContent = 'Clair';
    }
    
    // Get sidebar status
    if (sidebarStatus) {
        // Try to get sidebar state from Alpine.js
        const app = document.querySelector('[x-data]');
        if (app && app._x_dataStack) {
            const data = app._x_dataStack[0];
            if (data.sidebarCollapsed !== undefined) {
                sidebarStatus.textContent = data.sidebarCollapsed ? 'Collapsée' : 'Étendue';
            }
        }
    }
}

// Update status on load and resize
document.addEventListener('DOMContentLoaded', updateStatus);
window.addEventListener('resize', updateStatus);

// Update status every second to catch Alpine.js changes
setInterval(updateStatus, 1000);
</script>
@endsection
