{{-- Page de test de l'espacement de la sidebar --}}
@extends('layouts.app')

@section('title', 'Test Espacement Sidebar - EVON')
@section('description', 'Test de l\'espacement de la sidebar et du contenu principal')

@section('header-actions')
<div class="flex gap-2">
    <button class="btn-fullsize btn-fullsize-primary" onclick="toggleSidebar()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        Toggle Sidebar
    </button>
    <button class="btn-fullsize btn-fullsize-secondary" onclick="checkSpacing()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Vérifier Espacement
    </button>
</div>
@endsection

@section('content')
<div class="w-full">
    <!-- Test de l'espacement de la sidebar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test de l'Espacement de la Sidebar</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste l'espacement de la sidebar et s'assure qu'il n'y a pas d'espace blanc inutile.
        </p>
        
        <!-- Indicateur d'état de la sidebar -->
        <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-blue-800 dark:text-blue-200 font-semibold">
                    Sidebar: <span id="sidebar-status">Étendue</span> | 
                    Largeur écran: <span id="screen-width">-</span>px | 
                    Marge gauche: <span id="margin-left">-</span>px
                </span>
            </div>
        </div>

        <!-- Test de largeur -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-green-100 dark:bg-green-900 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800 dark:text-green-200 mb-2">Test 1</h3>
                <p class="text-green-600 dark:text-green-300 text-sm">Contenu de test pour vérifier l'espacement</p>
                <div class="mt-2 text-xs text-green-700 dark:text-green-300">
                    Largeur: <span class="font-mono" id="width-1">-</span>px
                </div>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-lg">
                <h3 class="font-semibold text-purple-800 dark:text-purple-200 mb-2">Test 2</h3>
                <p class="text-purple-600 dark:text-purple-300 text-sm">Contenu de test pour vérifier l'espacement</p>
                <div class="mt-2 text-xs text-purple-700 dark:text-purple-300">
                    Largeur: <span class="font-mono" id="width-2">-</span>px
                </div>
            </div>
            <div class="bg-orange-100 dark:bg-orange-900 p-4 rounded-lg">
                <h3 class="font-semibold text-orange-800 dark:text-orange-200 mb-2">Test 3</h3>
                <p class="text-orange-600 dark:text-orange-300 text-sm">Contenu de test pour vérifier l'espacement</p>
                <div class="mt-2 text-xs text-orange-700 dark:text-orange-300">
                    Largeur: <span class="font-mono" id="width-3">-</span>px
                </div>
            </div>
        </div>

        <!-- Test de bordure -->
        <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-lg p-6 text-white mb-6">
            <h3 class="text-xl font-bold mb-2">Test de Bordure</h3>
            <p class="mb-4">Cette section teste les bordures et s'assure qu'il n'y a pas d'espace blanc inutile.</p>
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

        <!-- Instructions de test -->
        <div class="bg-yellow-100 dark:bg-yellow-900 p-4 rounded-lg">
            <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Instructions de Test</h3>
            <ul class="text-yellow-700 dark:text-yellow-300 text-sm space-y-1">
                <li>• Redimensionnez la fenêtre pour tester la responsivité</li>
                <li>• Cliquez sur "Toggle Sidebar" pour tester l'espacement</li>
                <li>• Vérifiez qu'il n'y a pas d'espace blanc inutile à gauche</li>
                <li>• Testez sur mobile, tablet et desktop</li>
            </ul>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    // Toggle sidebar state
    if (window.Alpine) {
        const app = document.querySelector('[x-data]');
        if (app && app._x_dataStack) {
            const data = app._x_dataStack[0];
            if (data.sidebarCollapsed !== undefined) {
                data.sidebarCollapsed = !data.sidebarCollapsed;
            }
        }
    }
    updateStatus();
}

function checkSpacing() {
    const main = document.querySelector('main');
    const computedStyle = window.getComputedStyle(main);
    const marginLeft = computedStyle.marginLeft;
    
    document.getElementById('margin-left').textContent = marginLeft;
    updateStatus();
}

function updateStatus() {
    // Update sidebar status
    const sidebarStatus = document.getElementById('sidebar-status');
    const screenWidth = document.getElementById('screen-width');
    const marginLeft = document.getElementById('margin-left');
    
    // Get current window width
    screenWidth.textContent = window.innerWidth;
    
    // Get main element margin
    const main = document.querySelector('main');
    if (main) {
        const computedStyle = window.getComputedStyle(main);
        marginLeft.textContent = computedStyle.marginLeft;
    }
    
    // Update width measurements
    const elements = ['width-1', 'width-2', 'width-3'];
    elements.forEach((id, index) => {
        const element = document.getElementById(id);
        if (element) {
            const parent = element.closest('.bg-green-100, .bg-purple-100, .bg-orange-100');
            if (parent) {
                element.textContent = parent.offsetWidth;
            }
        }
    });
}

// Update status on load and resize
document.addEventListener('DOMContentLoaded', updateStatus);
window.addEventListener('resize', updateStatus);

// Update status every second to catch Alpine.js changes
setInterval(updateStatus, 1000);
</script>
@endsection
