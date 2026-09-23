<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif 
      class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'EVON') . ' - Electric Vehicle Charging Management')</title>
    <!-- Favicon -->
    @php
        $faviconUrl = \App\Helpers\Brand::favicon();
        $faviconType = str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/x-icon';
    @endphp
    <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <!-- Correctif global pour les erreurs dataset -->
    <script src="{{ asset('js/global-dataset-fix.js') }}"></script>
    <!-- Correctif pour les requêtes API de notifications -->
    <script src="{{ asset('js/notification-api-fix.js') }}"></script>
    <!-- Correctif pour le conteneur principal fixe -->
    <script src="{{ asset('js/main-content-fix.js') }}"></script>
    <script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981',
                        secondary: '#3b82f6',
                        success: '#22c55e',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                    },
                },
                fontFamily: {
                    sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                }
            }
        }
    </script>
    <style>
        /* Support RTL pour l'arabe */
        [dir="rtl"] .rtl-flip {
            direction: rtl;
        }
        
        [dir="rtl"] .rtl-text-right {
            text-align: right;
        }
        
        [dir="rtl"] .rtl-float-right {
            float: right;
        }
        
        [dir="rtl"] .rtl-mr-2 {
            margin-right: 0;
            margin-left: 0.5rem;
        }
        
        [dir="rtl"] .rtl-ml-2 {
            margin-left: 0;
            margin-right: 0.5rem;
        }
        
        /* Focus ring utility */
        .focus-ring {
            @apply focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2;
        }
        
        /* Layout fixe sans espace blanc */
        .layout-fixed {
            min-height: 100vh;
            background: #f8fafc;
            position: relative;
        }
        
        /* Main content - FIXE sans glissement */
        .main-fixed {
            min-height: 100vh;
            background: #f8fafc;
            width: 100%;
            position: relative;
            left: 0;
            transition: left 0.3s ease;
        }
        
        /* Desktop: Décaler le contenu pour la sidebar */
        @media (min-width: 1024px) {
            .main-fixed {
                left: 280px;
                width: calc(100% - 280px);
            }
            
            .main-fixed.sidebar-collapsed {
                left: 80px;
                width: calc(100% - 80px);
            }
        }
        
        /* Mobile: Pas de décalage */
        @media (max-width: 1023px) {
            .main-fixed {
                left: 0 !important;
                width: 100% !important;
            }
        }
        
        /* Sidebar fixe */
        .sidebar-fixed {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: white;
            border-right: 1px solid #e5e7eb;
            z-index: 50;
            transition: width 0.3s ease, left 0.3s ease;
        }
        
        .sidebar-fixed.collapsed {
            width: 80px;
        }
        
        /* Mobile sidebar */
        @media (max-width: 1023px) {
            .sidebar-fixed {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar-fixed.open {
                transform: translateX(0);
            }
        }
        
        /* Content area optimal sizing */
        .content-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        @media (min-width: 1024px) {
            .content-container {
                padding: 2rem;
            }
        }
        
        /* Card hover effects */
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Dark mode support */
        .dark .layout-fixed {
            background: #111827;
        }
        
        .dark .main-fixed {
            background: #111827;
        }
        
        .dark .sidebar-fixed {
            background: #1f2937;
            border-right-color: #374151;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 h-full" 
      x-data="dashboardData()" 
      :class="{ 'dark': darkMode }"
      x-init="init()">

    <!-- Layout fixe avec sidebar et contenu principal -->
    <div class="layout-fixed" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
        @auth
            @php
                // Vérifier si l'utilisateur a uniquement le rôle "user" (client public)
                // Les clients simples n'ont PAS accès à la sidebar
                $user = auth()->user();
                $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
                $isClientOnly = count($userRoles) === 1 && in_array('user', $userRoles);
                $hasOtherRoles = $user->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner']);
                $isClientOnly = $isClientOnly && !$hasOtherRoles;
            @endphp

            @if(!$isClientOnly)
                <!-- Sidebar fixe - UNIQUEMENT pour les non-clients -->
                <div class="sidebar-fixed" 
                     :class="{ 'collapsed': sidebarCollapsed, 'open': sidebarOpen }"
                     x-show="window.innerWidth >= 1024 || sidebarOpen">
                    @include('layouts.sidebar')
                </div>
            @endif

            <!-- Mobile sidebar overlay -->
            <div x-show="sidebarOpen && window.innerWidth < 1024" 
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>

            <!-- Main Content - FIXE -->
            <main class="main-fixed" 
                  :class="{ 'sidebar-collapsed': sidebarCollapsed }"
                  @sidebar-toggle.window="sidebarCollapsed = !sidebarCollapsed">
                
                <!-- Header fixe -->
                <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
                    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = !sidebarOpen" 
                                class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <!-- Desktop sidebar toggle -->
                        <button @click="sidebarCollapsed = !sidebarCollapsed" 
                                class="hidden lg:block p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                            </svg>
                        </button>

                        <!-- Page title -->
                        <div class="flex-1">
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                @yield('title', 'EVON')
                            </h1>
                        </div>

                        <!-- User menu -->
                        <div class="flex items-center space-x-4">
                            <!-- Notifications -->
                            <button class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.5 12a7.5 7.5 0 1115 0 7.5 7.5 0 01-15 0z"/>
                                </svg>
                            </button>

                            <!-- User dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="flex items-center space-x-3 p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                                    <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ substr(auth()->user()->name, 0, 2) }}
                                        </span>
                                    </div>
                                    <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ auth()->user()->name }}
                                    </span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- Dropdown menu -->
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
                                    <a href="{{ route('profile.edit') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Profil
                                    </a>
                                    <a href="{{ route('settings.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Paramètres
                                    </a>
                                    <hr class="my-1 border-gray-200 dark:border-gray-700">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Déconnexion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Content area -->
                <div class="content-container">
                    @if(View::hasSection('content'))
                        @yield('content')
                    @else
                        <!-- Default content when no content is yielded -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Welcome to EVON</h1>
                            <p class="text-gray-600 dark:text-gray-300">This is the main content area with the fixed layout design featuring both sidebar and header navigation.</p>
                            <div class="mt-4">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                                        {{ __('messages.go_to_dashboard') }}
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                                        Login
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        @endauth
    </div>

    <!-- Scripts -->
    <script>
        function dashboardData() {
            return {
                darkMode: false,
                sidebarOpen: false,
                sidebarCollapsed: false,
                
                init() {
                    // Initialize dark mode from localStorage
                    this.darkMode = localStorage.getItem('darkMode') === 'true';
                    this.updateDarkMode();
                    
                    // Initialize sidebar state from localStorage
                    this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    
                    // Listen for window resize
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) {
                            this.sidebarOpen = false;
                        }
                    });
                },
                
                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    this.updateDarkMode();
                    localStorage.setItem('darkMode', this.darkMode);
                },
                
                updateDarkMode() {
                    if (this.darkMode) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },
                
                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                    
                    // Dispatch event for other components
                    window.dispatchEvent(new CustomEvent('sidebar-toggle'));
                }
            }
        }
    </script>
    
    @stack('scripts')
</body>
</html>
