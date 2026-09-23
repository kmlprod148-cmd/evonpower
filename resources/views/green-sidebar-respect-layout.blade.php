{{-- Page de test - Sidebar Verte avec Layout Respecté --}}
@extends('layouts.app')

@section('title', 'Test Sidebar Verte - Layout Respecté - EVON')
@section('description', 'Test de la sidebar verte en respectant le layout existant')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    @auth
        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen && window.innerWidth < 1024" 
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;"></div>

        <!-- Main Container with Green Sidebar and Content -->
        <div class="flex min-h-screen" x-data="{ sidebarCollapsed: false, sidebarOpen: false }">
            <!-- Green Sidebar Component -->
            <x-sidebar-green />

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Top Navigation Bar -->
            <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <!-- Mobile Sidebar Toggle -->
                            <button @click="sidebarOpen = !sidebarOpen" 
                                    class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>

                            <!-- Page Title avec accent vert -->
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white ml-4 lg:ml-0">
                                <span class="text-green-600">Test Sidebar Verte</span>
                            </h1>
                        </div>

                        <!-- Right side -->
                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Language Switcher -->
                            @include('components.direct-language-switcher')

                            <!-- Theme Toggle -->
                            <button @click="toggleTheme()" 
                                    class="ml-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <svg x-show="!darkMode" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                                <svg x-show="darkMode" class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </button>

                            <!-- Notifications -->
                            @include('components.notification-bell')

                            <!-- User Dropdown -->
                            <div x-data="{ userMenuOpen: false }" class="ml-3 relative">
                                <button @click="userMenuOpen = !userMenuOpen" 
                                        class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <div class="h-8 w-8 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name ?? 'User' }}</span>
                                    <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- User Dropdown Menu -->
                                <div x-show="userMenuOpen" @click.away="userMenuOpen = false" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 dark:divide-gray-600"
                                     style="display: none;">
                                    <div class="py-1">
                                        <a href="{{ route('profile.edit') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Profil
                                        </a>
                                        <a href="{{ route('settings.index') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Paramètres
                                        </a>
                                    </div>
                                    <div class="py-1">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" 
                                                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                                </svg>
                                                Déconnexion
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="flex-1 min-h-screen bg-gray-50 dark:bg-gray-900">
                <!-- Page Header -->
                <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                    <span class="text-green-600">Test Sidebar Verte - Layout Respecté</span>
                                </h1>
                                <p class="mt-1 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                    Cette page teste la sidebar verte en respectant le layout existant d'EVON.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="w-full">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <!-- Test de la sidebar verte -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                <span class="text-green-600">Sidebar Verte avec Layout Respecté</span>
                            </h2>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                Cette page utilise le layout existant `app.blade.php` avec la sidebar verte comme composant.
                            </p>
                            
                            <!-- Indicateur d'état -->
                            <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-semibold">
                                        ✅ Layout Respecté | 
                                        Sidebar: <span class="text-green-200">Verte</span> | 
                                        Composant: <span class="text-green-200">x-sidebar-green</span> | 
                                        Design: <span class="text-green-200">Élégant</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Fonctionnalités de la sidebar verte -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-green-500 transition-colors">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 text-green-600">Layout Respecté</h3>
                                    <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                                        <li>• Utilise app.blade.php</li>
                                        <li>• Composant sidebar-green</li>
                                        <li>• Alpine.js intégré</li>
                                        <li>• Responsive design</li>
                                    </ul>
                                </div>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-green-500 transition-colors">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 text-green-600">Design Vert</h3>
                                    <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                                        <li>• Header gradient vert</li>
                                        <li>• Hover effects verts</li>
                                        <li>• Active states verts</li>
                                        <li>• Transitions fluides</li>
                                    </ul>
                                </div>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-green-500 transition-colors">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 text-green-600">Fonctionnalités</h3>
                                    <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                                        <li>• Collapse/Expand</li>
                                        <li>• Mobile overlay</li>
                                        <li>• User profile</li>
                                        <li>• Navigation complète</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Test des boutons verts -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-green-600">Boutons Verts</h3>
                                    <div class="space-y-3">
                                        <button class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl">
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

                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-green-600">Animations Vertes</h3>
                                    <div class="space-y-3">
                                        <button onclick="testGreenPulse()" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200">
                                            Test Pulse Vert
                                        </button>
                                        <button onclick="testGreenGlow()" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                            Test Glow Vert
                                        </button>
                                        <button onclick="testGreenSlide()" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200">
                                            Test Slide Vert
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Test de la grille avec touches vertes -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:border-green-500 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1 text-green-600">Énergie</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Gestion intelligente</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:border-green-500 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1 text-green-600">Analytics</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Données en temps réel</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:border-green-500 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1 text-green-600">Utilisateurs</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Gestion des comptes</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:border-green-500 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1 text-green-600">Paramètres</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Configuration avancée</p>
                                </div>
                            </div>

                            <!-- Instructions de test -->
                            <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 text-green-600">Instructions de Test</h3>
                                <ul class="text-gray-700 dark:text-gray-300 text-sm space-y-1">
                                    <li>• <strong>Layout Respecté</strong> : Utilise le layout existant app.blade.php</li>
                                    <li>• <strong>Composant Sidebar</strong> : Sidebar verte comme composant réutilisable</li>
                                    <li>• <strong>Toggle Sidebar</strong> : Testez le collapse/expand de la sidebar verte</li>
                                    <li>• <strong>Hover Effects</strong> : Survolez les éléments de navigation</li>
                                    <li>• <strong>Mobile</strong> : Testez sur différentes tailles d'écran</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            </div>
        </div>
    @endauth
</div>

<script>
function testGreenPulse() {
    const element = document.querySelector('.bg-white');
    if (element) {
        element.style.animation = 'pulse-green 1s ease-in-out';
        setTimeout(() => {
            element.style.animation = '';
        }, 1000);
    }
}

function testGreenGlow() {
    const element = document.querySelector('.bg-white');
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
    const element = document.querySelector('.bg-white');
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
    
    .hover\\:border-green-500:hover {
        border-color: #10b981;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }
`;
document.head.appendChild(style);
</script>
@endsection
