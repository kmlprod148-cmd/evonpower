<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif 
      class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4acf7b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <title>@yield('title', config('app.name', 'EVON') . ' - Electric Vehicle Charging Management')</title>
    
    <!-- Favicon -->
    @php
        $faviconUrl = \App\Helpers\Brand::favicon();
        $faviconType = str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/x-icon';
    @endphp
    <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    
    <!-- Alpine.js -->
    <script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
    
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 h-full antialiased" 
      x-data="appState()" 
      :class="{ 'dark': darkMode }"
      x-init="init()">

    <div class="flex h-full">
        @auth
            <!-- Sidebar -->
            @include('layouts.partials.evon-sidebar')
            
            <!-- Mobile Sidebar Overlay -->
            <div x-show="sidebarOpen" 
                 x-cloak
                 @click="sidebarOpen = false"
                 class="sidebar-overlay lg:hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>
        @endauth

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0"
             :class="{ 
                 'lg:ml-64': !sidebarCollapsed && $auth, 
                 'lg:ml-16': sidebarCollapsed && $auth,
                 'ml-0': !$auth
             }">
            
            @auth
                <!-- Header -->
                @include('layouts.partials.evon-header')
            @endauth

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-50 dark:bg-gray-900">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg shadow-sm fade-in" role="alert">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-lg shadow-sm fade-in" role="alert">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-lg shadow-sm fade-in" role="alert">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ session('warning') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg shadow-sm fade-in" role="alert">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ session('info') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Page Content -->
                <div class="content-container">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Alpine.js State Management -->
    <script>
        function appState() {
            return {
                // Sidebar state
                sidebarOpen: false,
                sidebarCollapsed: Alpine.$persist(false).as('sidebarCollapsed'),
                
                // Dark mode state
                darkMode: Alpine.$persist(
                    localStorage.getItem('theme') === 'dark' || 
                    (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)
                ).as('darkMode'),
                
                // Auth state
                $auth: {{ auth()->check() ? 'true' : 'false' }},
                
                // Initialize
                init() {
                    // Apply dark mode
                    if (this.darkMode) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    
                    // Watch for dark mode changes
                    this.$watch('darkMode', value => {
                        if (value) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('theme', 'dark');
                            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', '#1f2937');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('theme', 'light');
                            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', '#4acf7b');
                        }
                    });
                    
                    // Close sidebar on mobile when clicking outside
                    if (window.innerWidth < 1024) {
                        this.sidebarOpen = false;
                    }
                    
                    // Handle window resize
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) {
                            this.sidebarOpen = false;
                        }
                    });
                    
                    // Keyboard shortcuts
                    document.addEventListener('keydown', (e) => {
                        // Ctrl/Cmd + B: Toggle sidebar
                        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                            e.preventDefault();
                            this.sidebarCollapsed = !this.sidebarCollapsed;
                        }
                        
                        // Ctrl/Cmd + D: Toggle dark mode
                        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                            e.preventDefault();
                            this.darkMode = !this.darkMode;
                        }
                        
                        // Escape: Close mobile sidebar
                        if (e.key === 'Escape' && this.sidebarOpen) {
                            this.sidebarOpen = false;
                        }
                    });
                },
                
                // Toggle sidebar
                toggleSidebar() {
                    if (window.innerWidth < 1024) {
                        this.sidebarOpen = !this.sidebarOpen;
                    } else {
                        this.sidebarCollapsed = !this.sidebarCollapsed;
                    }
                },
                
                // Toggle dark mode
                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>

