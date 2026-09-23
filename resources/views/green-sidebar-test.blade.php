{{-- Page de test - Sidebar Verte Élégante --}}
@extends('layouts.app-green')

@section('title', 'Test Sidebar Verte - EVON')
@section('description', 'Test de la sidebar verte avec touches élégantes')

@section('content')
<div class="w-full">
    <!-- Test de la sidebar verte -->
    <div class="card-green p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="accent-green">Test Sidebar Verte Élégante</span>
        </h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste la sidebar verte avec des touches élégantes et un design moderne.
        </p>
        
        <!-- Indicateur d'état -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold">
                    ✅ Sidebar Verte Active | 
                    État: <span id="sidebar-status">Étendue</span> | 
                    Couleur: <span class="text-green-200">Vert Élégant</span> | 
                    Design: <span class="text-green-200">Moderne</span>
                </span>
            </div>
        </div>

        <!-- Fonctionnalités de la sidebar verte -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="card-green p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 accent-green">Design Vert</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Gradient vert élégant</li>
                    <li>• Header avec effet glass</li>
                    <li>• Navigation avec hover vert</li>
                    <li>• Transitions fluides</li>
                </ul>
            </div>
            <div class="card-green p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 accent-green">Interactions</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Hover avec effet vert</li>
                    <li>• Active state avec gradient</li>
                    <li>• Icons avec animations</li>
                    <li>• Tooltips informatifs</li>
                </ul>
            </div>
            <div class="card-green p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 accent-green">Responsive</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Mobile: Overlay</li>
                    <li>• Desktop: Fixe</li>
                    <li>• Collapse: 80px</li>
                    <li>• Expand: 280px</li>
                </ul>
            </div>
        </div>

        <!-- Test des boutons verts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="card-green p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 accent-green">Boutons Verts</h3>
                <div class="space-y-3">
                    <button class="btn-green w-full">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Bouton Principal Vert
                    </button>
                    <button class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                        Bouton Secondaire
                    </button>
                </div>
            </div>

            <div class="card-green p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 accent-green">Animations Vertes</h3>
                <div class="space-y-3">
                    <button onclick="testGreenPulse()" class="btn-green w-full">
                        Test Pulse Vert
                    </button>
                    <button onclick="testGreenGlow()" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Test Glow Vert
                    </button>
                    <button onclick="testGreenSlide()" class="btn-green w-full">
                        Test Slide Vert
                    </button>
                </div>
            </div>
        </div>

        <!-- Test de la grille avec touches vertes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card-green p-4 text-center hover:border-accent-green">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1 accent-green">Énergie</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Gestion intelligente</p>
            </div>
            <div class="card-green p-4 text-center hover:border-accent-green">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1 accent-green">Analytics</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Données en temps réel</p>
            </div>
            <div class="card-green p-4 text-center hover:border-accent-green">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1 accent-green">Utilisateurs</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Gestion des comptes</p>
            </div>
            <div class="card-green p-4 text-center hover:border-accent-green">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1 accent-green">Paramètres</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Configuration avancée</p>
            </div>
        </div>

        <!-- Instructions de test -->
        <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 accent-green">Instructions de Test</h3>
            <ul class="text-gray-700 dark:text-gray-300 text-sm space-y-1">
                <li>• <strong>Toggle Sidebar</strong> : Testez le collapse/expand de la sidebar verte</li>
                <li>• <strong>Hover Effects</strong> : Survolez les éléments de navigation</li>
                <li>• <strong>Active States</strong> : Vérifiez les états actifs avec gradient vert</li>
                <li>• <strong>Mobile</strong> : Testez sur différentes tailles d'écran</li>
                <li>• <strong>Animations</strong> : Testez les animations vertes</li>
            </ul>
        </div>
    </div>
</div>

<script>
function testGreenPulse() {
    const element = document.querySelector('.card-green');
    if (element) {
        element.style.animation = 'pulse-green 1s ease-in-out';
        setTimeout(() => {
            element.style.animation = '';
        }, 1000);
    }
}

function testGreenGlow() {
    const element = document.querySelector('.card-green');
    if (element) {
        element.style.boxShadow = '0 0 20px rgba(16, 185, 129, 0.5)';
        element.style.borderColor = '#10b981';
        setTimeout(() => {
            element.style.boxShadow = '';
            element.style.borderColor = '';
        }, 2000);
    }
}

function testGreenSlide() {
    const element = document.querySelector('.card-green');
    if (element) {
        element.style.transform = 'translateX(-10px)';
        element.style.borderLeftColor = '#10b981';
        element.style.borderLeftWidth = '4px';
        setTimeout(() => {
            element.style.transform = '';
            element.style.borderLeftColor = '';
            element.style.borderLeftWidth = '';
        }, 1000);
    }
}

function updateStatus() {
    // Update sidebar status
    const sidebarStatus = document.getElementById('sidebar-status');
    
    // Get sidebar status from Alpine.js
    const app = document.querySelector('[x-data]');
    if (app && app._x_dataStack) {
        const data = app._x_dataStack[0];
        if (data.sidebarCollapsed !== undefined) {
            sidebarStatus.textContent = data.sidebarCollapsed ? 'Collapsée' : 'Étendue';
        }
    }
}

// Update status on load and resize
document.addEventListener('DOMContentLoaded', updateStatus);
window.addEventListener('resize', updateStatus);

// Update status every second to catch Alpine.js changes
setInterval(updateStatus, 1000);

// Add green animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse-green {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
        }
    }
    
    .card-green:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }
`;
document.head.appendChild(style);
</script>
@endsection
