{{-- Page de test de la sidebar moderne --}}
@extends('layouts.app-precompiled')

@section('title', 'Test Sidebar Moderne - EVON')
@section('description', 'Test de la sidebar moderne et élégante')

@section('header-actions')
<div class="flex gap-2">
    <button class="btn-fullsize btn-fullsize-primary" onclick="toggleSidebar()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        Toggle Sidebar
    </button>
    <button class="btn-fullsize btn-fullsize-secondary" onclick="testAnimations()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Test Animations
    </button>
</div>
@endsection

@section('content')
<div class="w-full">
    <!-- Test de la sidebar moderne -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Test de la Sidebar Moderne</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Cette page teste la nouvelle sidebar moderne avec design élégant et animations fluides.
        </p>
        
        <!-- Indicateur d'état -->
        <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-blue-800 dark:text-blue-200 font-semibold">
                    ✅ Sidebar Moderne Active | 
                    État: <span id="sidebar-status">Étendue</span> | 
                    Largeur: <span id="sidebar-width">-</span>px
                </span>
            </div>
        </div>

        <!-- Fonctionnalités testées -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-green-100 dark:bg-green-900 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800 dark:text-green-200 mb-2">Design Moderne</h3>
                <ul class="text-green-600 dark:text-green-300 text-sm space-y-1">
                    <li>• Gradient header élégant</li>
                    <li>• Animations fluides</li>
                    <li>• Hover effects avancés</li>
                    <li>• Badges informatifs</li>
                </ul>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-lg">
                <h3 class="font-semibold text-purple-800 dark:text-purple-200 mb-2">Navigation Intelligente</h3>
                <ul class="text-purple-600 dark:text-purple-300 text-sm space-y-1">
                    <li>• Sections collapsibles</li>
                    <li>• Détection automatique</li>
                    <li>• États actifs dynamiques</li>
                    <li>• Tooltips contextuels</li>
                </ul>
            </div>
            <div class="bg-orange-100 dark:bg-orange-900 p-4 rounded-lg">
                <h3 class="font-semibold text-orange-800 dark:text-orange-200 mb-2">Responsive Parfait</h3>
                <ul class="text-orange-600 dark:text-orange-300 text-sm space-y-1">
                    <li>• Mobile optimisé</li>
                    <li>• Collapse intelligent</li>
                    <li>• Transitions fluides</li>
                    <li>• Performance optimale</li>
                </ul>
            </div>
        </div>

        <!-- Test des animations -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-6 text-white mb-6">
            <h3 class="text-xl font-bold mb-2">Test des Animations</h3>
            <p class="mb-4">Cliquez sur les boutons pour tester les animations de la sidebar.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <button onclick="testHoverEffects()" class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
                    <h4 class="font-semibold mb-2">Hover Effects</h4>
                    <p class="text-sm">Test des effets de survol</p>
                </button>
                <button onclick="testTransitions()" class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
                    <h4 class="font-semibold mb-2">Transitions</h4>
                    <p class="text-sm">Test des transitions</p>
                </button>
                <button onclick="testCollapse()" class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
                    <h4 class="font-semibold mb-2">Collapse</h4>
                    <p class="text-sm">Test du collapse</p>
                </button>
                <button onclick="testSections()" class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
                    <h4 class="font-semibold mb-2">Sections</h4>
                    <p class="text-sm">Test des sections</p>
                </button>
            </div>
        </div>

        <!-- Instructions de test -->
        <div class="bg-yellow-100 dark:bg-yellow-900 p-4 rounded-lg">
            <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Instructions de Test</h3>
            <ul class="text-yellow-700 dark:text-yellow-300 text-sm space-y-1">
                <li>• <strong>Toggle Sidebar</strong> : Testez le collapse/expand</li>
                <li>• <strong>Hover Effects</strong> : Survolez les éléments de navigation</li>
                <li>• <strong>Sections</strong> : Cliquez sur les en-têtes de section</li>
                <li>• <strong>Mobile</strong> : Testez sur différentes tailles d'écran</li>
                <li>• <strong>Animations</strong> : Observez les transitions fluides</li>
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

function testAnimations() {
    // Test all animations
    testHoverEffects();
    setTimeout(() => testTransitions(), 500);
    setTimeout(() => testCollapse(), 1000);
    setTimeout(() => testSections(), 1500);
}

function testHoverEffects() {
    console.log('Testing hover effects...');
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach((item, index) => {
        setTimeout(() => {
            item.style.transform = 'translateX(4px)';
            setTimeout(() => {
                item.style.transform = '';
            }, 200);
        }, index * 100);
    });
}

function testTransitions() {
    console.log('Testing transitions...');
    const sidebar = document.querySelector('.sidebar-modern');
    if (sidebar) {
        sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        sidebar.style.transform = 'scale(0.98)';
        setTimeout(() => {
            sidebar.style.transform = 'scale(1)';
        }, 300);
    }
}

function testCollapse() {
    console.log('Testing collapse...');
    toggleSidebar();
    setTimeout(() => {
        toggleSidebar();
    }, 1000);
}

function testSections() {
    console.log('Testing sections...');
    const sections = document.querySelectorAll('.section-header');
    sections.forEach((section, index) => {
        setTimeout(() => {
            section.click();
            setTimeout(() => {
                section.click();
            }, 500);
        }, index * 300);
    });
}

function updateStatus() {
    // Update sidebar status
    const sidebarStatus = document.getElementById('sidebar-status');
    const sidebarWidth = document.getElementById('sidebar-width');
    
    // Get sidebar element
    const sidebar = document.querySelector('.sidebar-modern');
    if (sidebar) {
        const computedStyle = window.getComputedStyle(sidebar);
        sidebarWidth.textContent = sidebar.offsetWidth;
        
        if (sidebar.classList.contains('sidebar-collapsed')) {
            sidebarStatus.textContent = 'Collapsée';
        } else {
            sidebarStatus.textContent = 'Étendue';
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
