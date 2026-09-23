{{-- Page de test - Contenu principal fixe --}}
@extends('layouts.app-fixed')

@section('title', 'Test Contenu Principal Fixe - EVON')
@section('description', 'Test du contenu principal qui ne glisse plus vers la droite')

@section('content')
<div class="w-full">
    <!-- Test du contenu principal fixe -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test Contenu Principal Fixe</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste que le contenu principal ne glisse plus vers la droite et reste fixe.
        </p>
        
        <!-- Indicateur d'état -->
        <div class="bg-gradient-to-r from-green-500 to-blue-600 text-white p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold">
                    ✅ Contenu Principal Fixe | 
                    Sidebar: <span id="sidebar-status">Étendue</span> | 
                    Position: <span id="content-position">-</span>px | 
                    Largeur: <span id="content-width">-</span>px
                </span>
            </div>
        </div>

        <!-- Test de bordure rouge pour vérifier la position -->
        <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
            <h3 class="text-xl font-bold mb-2">Test de Position Fixe</h3>
            <p class="mb-2">Cette section rouge doit rester à la même position relative.</p>
            <p class="text-sm">Si le contenu glisse vers la droite, la correction n'a pas fonctionné.</p>
        </div>

        <!-- Fonctionnalités testées -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Position Fixe</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Pas de glissement</li>
                    <li>• Position stable</li>
                    <li>• Largeur adaptée</li>
                    <li>• Transitions fluides</li>
                </ul>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Responsive Design</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• Mobile: Pas de décalage</li>
                    <li>• Desktop: Décalage adapté</li>
                    <li>• Sidebar: Collapse/expand</li>
                    <li>• Largeur: Calculée</li>
                </ul>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Performance</h3>
                <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                    <li>• CSS optimisé</li>
                    <li>• Transitions GPU</li>
                    <li>• Pas de reflow</li>
                    <li>• Chargement rapide</li>
                </ul>
            </div>
        </div>

        <!-- Test des composants -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test des Boutons</h3>
                <div class="space-y-3">
                    <button class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Bouton Principal
                    </button>
                    <button class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                        Bouton Secondaire
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test des Animations</h3>
                <div class="space-y-3">
                    <button onclick="testFadeIn()" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                        Test Fade In
                    </button>
                    <button onclick="testSlideIn()" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Test Slide In
                    </button>
                    <button onclick="testLoading()" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                        Test Loading
                    </button>
                </div>
            </div>
        </div>

        <!-- Test de la grille -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Énergie</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Gestion intelligente</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Analytics</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Données en temps réel</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Utilisateurs</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">Gestion des comptes</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
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
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Instructions de Test</h3>
            <ul class="text-gray-700 dark:text-gray-300 text-sm space-y-1">
                <li>• <strong>Toggle Sidebar</strong> : Testez le collapse/expand de la sidebar</li>
                <li>• <strong>Position Fixe</strong> : Le contenu ne doit pas glisser vers la droite</li>
                <li>• <strong>Mobile</strong> : Testez sur différentes tailles d'écran</li>
                <li>• <strong>Largeur Adaptée</strong> : La largeur doit s'adapter à l'état de la sidebar</li>
                <li>• <strong>Transitions</strong> : Les transitions doivent être fluides</li>
            </ul>
        </div>
    </div>
</div>

<script>
function testFadeIn() {
    const element = document.querySelector('.bg-white');
    if (element) {
        element.style.opacity = '0';
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transition = 'opacity 0.3s ease';
        }, 100);
    }
}

function testSlideIn() {
    const element = document.querySelector('.bg-white');
    if (element) {
        element.style.transform = 'translateY(-10px)';
        element.style.opacity = '0';
        setTimeout(() => {
            element.style.transform = 'translateY(0)';
            element.style.opacity = '1';
            element.style.transition = 'all 0.3s ease';
        }, 100);
    }
}

function testLoading() {
    const element = document.querySelector('.bg-white');
    if (element) {
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.style.background = 'linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent)';
        element.style.backgroundSize = '200% 100%';
        element.style.animation = 'loading 1.5s infinite';
        
        setTimeout(() => {
            element.style.background = '';
            element.style.animation = '';
        }, 2000);
    }
}

function updateStatus() {
    // Update sidebar status
    const sidebarStatus = document.getElementById('sidebar-status');
    const contentPosition = document.getElementById('content-position');
    const contentWidth = document.getElementById('content-width');
    
    // Get main element position and width
    const main = document.querySelector('.main-fixed');
    if (main) {
        const computedStyle = window.getComputedStyle(main);
        contentPosition.textContent = computedStyle.left;
        contentWidth.textContent = computedStyle.width;
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

// Add loading animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes loading {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
`;
document.head.appendChild(style);

// Charger le script de test du layout
const testScript = document.createElement('script');
testScript.src = '{{ asset("js/layout-fix-test.js") }}';
document.head.appendChild(testScript);
</script>
@endsection
