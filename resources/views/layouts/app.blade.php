<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif 
      class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4acf7b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="steve-websocket-url" content="{{ config('steve.websocket_url', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/') }}">
    <meta name="steve-api-url" content="{{ config('steve.api_url', 'http://158.69.27.239:8080') }}">
    <title>@yield('title', config('app.name', 'EVON'))</title>
    
    @php
        // Light mode for pages that must load with minimal assets
        $isLightPage = ($lightPage ?? false) || request()->routeIs('charging-points.create.confirm');
    @endphp
    
    <!-- Favicon -->
    @php
        $faviconUrl = \App\Helpers\Brand::favicon();
        $faviconType = str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/x-icon';
    @endphp
    <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    
    <!-- Google Fonts - Inter & Arabic Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @if(app()->getLocale() === 'ar')
        <!-- Polices arabes optimisées -->
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Cairo:wght@300;400;500;600;700;800&family=Noto+Sans+Arabic:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Critical RTL inline styles: prevent sidebar FOUC before external CSS loads -->
        <style>
            /* Mobile: hide RTL sidebar on right, prevent LTR flash */
            @media (max-width: 1023px) {
                #main-sidebar, .evon-sidebar {
                    position: fixed !important;
                    top: 0 !important;
                    right: 0 !important;
                    left: auto !important;
                    bottom: 0 !important;
                    transform: translateX(100%) !important;
                    visibility: hidden !important;
                }
                #main-sidebar.translate-x-0, .evon-sidebar.translate-x-0 {
                    transform: translateX(0) !important;
                    visibility: visible !important;
                }
            }
            /* Desktop: sidebar right side via row-reverse */
            @media (min-width: 1024px) {
                .flex.h-screen {
                    flex-direction: row-reverse !important;
                }
                #main-sidebar, .evon-sidebar {
                    position: relative !important;
                    transform: none !important;
                    visibility: visible !important;
                }
            }
        </style>
    @endif
    
    @stack('meta')
    
    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : time() }}">
    <link rel="preload" as="style" href="{{ asset('css/evon-layout.css') }}?v={{ file_exists(public_path('css/evon-layout.css')) ? filemtime(public_path('css/evon-layout.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/evon-layout.css') }}?v={{ file_exists(public_path('css/evon-layout.css')) ? filemtime(public_path('css/evon-layout.css')) : time() }}"></noscript>
    <link rel="preload" as="style" href="{{ asset('css/dashboard-graphics.css') }}?v={{ file_exists(public_path('css/dashboard-graphics.css')) ? filemtime(public_path('css/dashboard-graphics.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/dashboard-graphics.css') }}?v={{ file_exists(public_path('css/dashboard-graphics.css')) ? filemtime(public_path('css/dashboard-graphics.css')) : time() }}"></noscript>
    <link rel="preload" as="style" href="{{ asset('css/charts-animations.css') }}?v={{ file_exists(public_path('css/charts-animations.css')) ? filemtime(public_path('css/charts-animations.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/charts-animations.css') }}?v={{ file_exists(public_path('css/charts-animations.css')) ? filemtime(public_path('css/charts-animations.css')) : time() }}"></noscript>
    
    @if(!$isLightPage)
        {{-- Premium CSS (skipped on light pages) --}}
        <link rel="preload" as="style" href="{{ asset('css/dashboard-premium.css') }}?v={{ file_exists(public_path('css/dashboard-premium.css')) ? filemtime(public_path('css/dashboard-premium.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/dashboard-premium.css') }}?v={{ file_exists(public_path('css/dashboard-premium.css')) ? filemtime(public_path('css/dashboard-premium.css')) : time() }}"></noscript>
        <link rel="preload" as="style" href="{{ asset('css/dashboard-compact-design.css') }}?v={{ file_exists(public_path('css/dashboard-compact-design.css')) ? filemtime(public_path('css/dashboard-compact-design.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/dashboard-compact-design.css') }}?v={{ file_exists(public_path('css/dashboard-compact-design.css')) ? filemtime(public_path('css/dashboard-compact-design.css')) : time() }}"></noscript>
        <link rel="preload" as="style" href="{{ asset('css/mobile-menu-premium.css') }}?v={{ file_exists(public_path('css/mobile-menu-premium.css')) ? filemtime(public_path('css/mobile-menu-premium.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/mobile-menu-premium.css') }}?v={{ file_exists(public_path('css/mobile-menu-premium.css')) ? filemtime(public_path('css/mobile-menu-premium.css')) : time() }}"></noscript>
        <link rel="preload" as="style" href="{{ asset('css/responsive-premium.css') }}?v={{ file_exists(public_path('css/responsive-premium.css')) ? filemtime(public_path('css/responsive-premium.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/responsive-premium.css') }}?v={{ file_exists(public_path('css/responsive-premium.css')) ? filemtime(public_path('css/responsive-premium.css')) : time() }}"></noscript>
        <link rel="preload" as="style" href="{{ asset('css/animations-premium.css') }}?v={{ file_exists(public_path('css/animations-premium.css')) ? filemtime(public_path('css/animations-premium.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/animations-premium.css') }}?v={{ file_exists(public_path('css/animations-premium.css')) ? filemtime(public_path('css/animations-premium.css')) : time() }}"></noscript>
    @endif
    
    @if(app()->getLocale() === 'ar')
        {{-- RTL CSS: loaded synchronously to prevent layout flash (FOUC) --}}
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
        <link rel="stylesheet" href="{{ asset('css/rtl-enhanced.css') }}?v={{ file_exists(public_path('css/rtl-enhanced.css')) ? filemtime(public_path('css/rtl-enhanced.css')) : time() }}">
        <link rel="stylesheet" href="{{ asset('css/rtl-dashboard-enhanced.css') }}?v={{ file_exists(public_path('css/rtl-dashboard-enhanced.css')) ? filemtime(public_path('css/rtl-dashboard-enhanced.css')) : time() }}">
        <link rel="stylesheet" href="{{ asset('css/rtl-header-enhanced.css') }}?v={{ file_exists(public_path('css/rtl-header-enhanced.css')) ? filemtime(public_path('css/rtl-header-enhanced.css')) : time() }}">
        <link rel="stylesheet" href="{{ asset('css/sidebar-rtl-enhanced.css') }}?v={{ file_exists(public_path('css/sidebar-rtl-enhanced.css')) ? filemtime(public_path('css/sidebar-rtl-enhanced.css')) : time() }}">
        <link rel="preload" as="style" href="{{ asset('css/rtl-animations.css') }}?v={{ file_exists(public_path('css/rtl-animations.css')) ? filemtime(public_path('css/rtl-animations.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/rtl-animations.css') }}?v={{ file_exists(public_path('css/rtl-animations.css')) ? filemtime(public_path('css/rtl-animations.css')) : time() }}"></noscript>
        <!-- RTL Sidebar Fix - MUST be last to override all other RTL rules -->
        <link rel="stylesheet" href="{{ asset('css/rtl-sidebar-fix.css') }}?v={{ file_exists(public_path('css/rtl-sidebar-fix.css')) ? filemtime(public_path('css/rtl-sidebar-fix.css')) : time() }}">
    @else
        <link rel="preload" as="style" href="{{ asset('css/sidebar-rtl-enhanced.css') }}?v={{ file_exists(public_path('css/sidebar-rtl-enhanced.css')) ? filemtime(public_path('css/sidebar-rtl-enhanced.css')) : time() }}" onload="this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/sidebar-rtl-enhanced.css') }}?v={{ file_exists(public_path('css/sidebar-rtl-enhanced.css')) ? filemtime(public_path('css/sidebar-rtl-enhanced.css')) : time() }}"></noscript>
    @endif
    
    {{-- Header & Mobile Fixes --}}
    <link rel="preload" as="style" href="{{ asset('css/header-fix.css') }}?v={{ file_exists(public_path('css/header-fix.css')) ? filemtime(public_path('css/header-fix.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/header-fix.css') }}?v={{ file_exists(public_path('css/header-fix.css')) ? filemtime(public_path('css/header-fix.css')) : time() }}"></noscript>
    <link rel="preload" as="style" href="{{ asset('css/user-menu.css') }}?v={{ file_exists(public_path('css/user-menu.css')) ? filemtime(public_path('css/user-menu.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/user-menu.css') }}?v={{ file_exists(public_path('css/user-menu.css')) ? filemtime(public_path('css/user-menu.css')) : time() }}"></noscript>
    <link rel="preload" as="style" href="{{ asset('css/mobile-enhanced.css') }}?v={{ file_exists(public_path('css/mobile-enhanced.css')) ? filemtime(public_path('css/mobile-enhanced.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/mobile-enhanced.css') }}?v={{ file_exists(public_path('css/mobile-enhanced.css')) ? filemtime(public_path('css/mobile-enhanced.css')) : time() }}"></noscript>
    <link rel="preload" as="style" href="{{ asset('css/mobile-top-utility-bar.css') }}?v={{ file_exists(public_path('css/mobile-top-utility-bar.css')) ? filemtime(public_path('css/mobile-top-utility-bar.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/mobile-top-utility-bar.css') }}?v={{ file_exists(public_path('css/mobile-top-utility-bar.css')) ? filemtime(public_path('css/mobile-top-utility-bar.css')) : time() }}"></noscript>
    <link rel="preload" as="style" href="{{ asset('css/mobile-floating-widgets.css') }}?v={{ file_exists(public_path('css/mobile-floating-widgets.css')) ? filemtime(public_path('css/mobile-floating-widgets.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/mobile-floating-widgets.css') }}?v={{ file_exists(public_path('css/mobile-floating-widgets.css')) ? filemtime(public_path('css/mobile-floating-widgets.css')) : time() }}"></noscript>
    
    {{-- Micro-interactions CSS --}}
    <link rel="preload" as="style" href="{{ asset('css/micro-interactions.css') }}?v={{ file_exists(public_path('css/micro-interactions.css')) ? filemtime(public_path('css/micro-interactions.css')) : time() }}" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/micro-interactions.css') }}?v={{ file_exists(public_path('css/micro-interactions.css')) ? filemtime(public_path('css/micro-interactions.css')) : time() }}"></noscript>
    
    {{-- Scripts CRITIQUES --}}
    {{-- Alpine Components Logic (MUST be before Alpine.js) --}}
    <script src="{{ asset('js/alpine-components.js') }}?v={{ filemtime(public_path('js/alpine-components.js')) }}"></script>
    
    {{-- Theme Initialization - Must run before Alpine.js --}}
    <script>
        (function() {
            // Get theme from localStorage or use system default
            const storedTheme = localStorage.getItem('theme') || 'system';
            
            // Function to apply theme
            function applyTheme(theme) {
                let effectiveTheme = theme;
                
                if (theme === 'system') {
                    effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                
                if (effectiveTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            
            // Apply theme immediately
            applyTheme(storedTheme);
            
            // Listen for system theme changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                    const currentTheme = localStorage.getItem('theme') || 'system';
                    if (currentTheme === 'system') {
                        applyTheme('system');
                    }
                });
            }
            
            // Expose theme functions globally
            window.themeManager = {
                setTheme: function(theme) {
                    localStorage.setItem('theme', theme);
                    applyTheme(theme);
                },
                getTheme: function() {
                    return storedTheme;
                },
                toggle: function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const newTheme = isDark ? 'light' : 'dark';
                    this.setTheme(newTheme);
                    return newTheme;
                }
            };
        })();
    </script>
    
    {{-- Alpine.js via CDN --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Fallback fixes --}}
    <script defer src="{{ asset('js/global-dataset-fix.js') }}"></script>
    @if(!$isLightPage)
        <script defer src="{{ asset('js/notification-api-fix-v2.js') }}"></script>
    @endif
    <script defer src="{{ asset('js/ultimate-csrf-fix-iphone.js') }}"></script>
    <script defer src="{{ asset('js/locale-session-manager.js') }}"></script>
    <script defer src="{{ asset('js/mobile-enhancements-v2.js') }}?v={{ filemtime(public_path('js/mobile-enhancements-v2.js')) }}"></script>
    <script defer src="{{ asset('js/mobile-interactions.js') }}?v={{ file_exists(public_path('js/mobile-interactions.js')) ? filemtime(public_path('js/mobile-interactions.js')) : time() }}"></script>
    <script defer src="{{ asset('js/mobile-top-bar-enhancements.js') }}?v={{ file_exists(public_path('js/mobile-top-bar-enhancements.js')) ? filemtime(public_path('js/mobile-top-bar-enhancements.js')) : time() }}"></script>
    
    @if(app()->getLocale() === 'ar')
        {{-- RTL Enhancements --}}
        <script defer src="{{ asset('js/rtl-enhancements.js') }}?v={{ file_exists(public_path('js/rtl-enhancements.js')) ? filemtime(public_path('js/rtl-enhancements.js')) : time() }}"></script>
    @endif
    
    @stack('styles')
</head>
<body class="h-screen overflow-hidden bg-gray-50 dark:bg-gray-900 transition-colors duration-200 font-sans" 
      x-data="window.appState ? window.appState() : {}" 
      :class="{ 'sidebar-open': sidebarOpen }">
    <!-- Sidebar ready script - prevents FOUC -->
    <script>
        // Add sidebar-ready class after Alpine initializes
        document.addEventListener('alpine:initialized', function() {
            document.body.classList.add('sidebar-ready');
        });
        // Fallback if Alpine already loaded
        if (window.Alpine) {
            document.body.classList.add('sidebar-ready');
        }
        // Extra fallback after short delay
        setTimeout(function() {
            document.body.classList.add('sidebar-ready');
        }, 100);
    </script>

    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="evon-skip-link">
        {{ __('messages.skip_to_content') ?? 'Aller au contenu principal' }}
    </a>
    
    <div class="flex h-screen">
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" 
             x-cloak
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="evon-mobile-sidebar-overlay lg:hidden"
             aria-hidden="true"></div>

        <!-- Sidebar -->
        <aside id="main-sidebar"
               class="evon-sidebar fixed inset-y-0 top-0 z-50 {{ app()->getLocale() === 'ar' ? 'right-0' : 'left-0' }} {{ app()->getLocale() === 'ar' ? 'translate-x-full' : '-translate-x-full' }} lg:translate-x-0 lg:static lg:z-auto"
               :class="{
                   'translate-x-0 !transform-none lg:!transform-none': sidebarOpen,
                   '{{ app()->getLocale() === 'ar' ? 'translate-x-full' : '-translate-x-full' }} lg:translate-x-0': !sidebarOpen,
                   'evon-sidebar-collapsed': sidebarCollapsed
               }"
               role="navigation"
               aria-label="{{ __('messages.main_menu') }}"
               aria-hidden="false">
            @include('layouts.partials.evon-sidebar')
        </aside>

        @php $isRtl = app()->getLocale() === 'ar'; @endphp
        <!-- Sidebar Toggle Button — outside sidebar to avoid will-change:transform stacking context -->
        <button @click="sidebarCollapsed = !sidebarCollapsed"
                class="hidden lg:flex fixed top-14 z-[9999] items-center justify-center border border-emerald-100 bg-white text-emerald-600 shadow-md transition-all duration-300 hover:scale-110 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
                :class="sidebarCollapsed
                    ? '{{ $isRtl ? 'right-[62px]' : 'left-[62px]' }}'
                    : '{{ $isRtl ? 'right-[326px]' : 'left-[326px]' }}'"
                style="width:20px;height:20px;border-radius:9999px;"
                :title="sidebarCollapsed ? '{{ __('Expand') }}' : '{{ __('Collapse') }}'"
                :aria-label="sidebarCollapsed ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'"
                aria-controls="main-sidebar"
                :aria-expanded="!sidebarCollapsed">
            <span class="flex items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm transition-colors duration-200 hover:bg-emerald-600"
                  style="width:12px;height:12px;">
                <svg x-show="!sidebarCollapsed" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:8px;height:8px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7' }}"/>
                </svg>
                <svg x-show="sidebarCollapsed" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:8px;height:8px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7' }}"/>
                </svg>
            </span>
        </button>

        <!-- Main Content Area - 80% width on desktop -->
        <div class="flex-1 flex flex-col min-h-0 w-full">
            <!-- Page Content -->
            <main id="main-content" 
                  class="evon-main" 
                  role="main"
                  tabindex="-1">
                <x-app-header 
                    :showStats="isset($showHeaderStats) && $showHeaderStats" 
                    :stats="$headerStats ?? $stats ?? []"
                    :title="$pageTitle ?? null"
                    :subtitle="$pageSubtitle ?? null"
                    :breadcrumbs="$breadcrumbs ?? []"
                />
                <div class="evon-content px-4 sm:px-6 lg:px-8">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="evon-alert-success animate-fade-in" role="alert">
                            <div class="evon-alert-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="evon-alert-content">
                                <p class="font-medium">{{ __('messages.success') }}</p>
                                <p class="text-sm">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="evon-alert-danger animate-fade-in" role="alert">
                            <div class="evon-alert-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="evon-alert-content">
                                <p class="font-medium">{{ __('messages.error') }}</p>
                                <p class="text-sm">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="evon-alert-danger animate-fade-in" role="alert">
                            <div class="evon-alert-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="evon-alert-content">
                                <p class="font-medium">{{ __('messages.validation_errors') }}</p>
                                <ul class="mt-2 text-sm list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Page Content -->
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Premium -->
    @auth
        @include('components.mobile-bottom-nav-premium')
    @endauth

    <!-- Mobile Search Modal -->
    @auth
        @include('components.mobile-search-modal')
    @endauth

    <!-- Mobile Floating Widgets (Alternative moderne à la top bar) -->
    @auth
        <x-mobile-floating-widgets />
    @endauth

    {{-- CRITICAL FIX: Inline script for locale URL persistence --}}
    @include('components.locale-url-fix-inline')
    {{-- Mise à jour solde client après paiement par crédit --}}
    <script defer src="{{ asset('js/wallet-balance-update.js') }}?v={{ file_exists(public_path('js/wallet-balance-update.js')) ? filemtime(public_path('js/wallet-balance-update.js')) : time() }}"></script>
    
    @if(!$isLightPage)
        {{-- Dashboard Premium JS (skipped on light pages) --}}
        <script defer src="{{ asset('js/dashboard-premium.js') }}?v={{ file_exists(public_path('js/dashboard-premium.js')) ? filemtime(public_path('js/dashboard-premium.js')) : time() }}"></script>
        
        {{-- Mobile Menu Premium JS --}}
        <script defer src="{{ asset('js/mobile-menu-premium.js') }}?v={{ file_exists(public_path('js/mobile-menu-premium.js')) ? filemtime(public_path('js/mobile-menu-premium.js')) : time() }}"></script>
    @endif
  
    @stack('scripts')
</body>
</html>
