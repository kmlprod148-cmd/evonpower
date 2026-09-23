<!-- Layout avec sidebar pour utilisateurs avec rôles (admin, integrator, operator, etc.) -->
<div class="flex h-screen" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
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
         class="evon-mobile-sidebar-overlay"
         aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-auto"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           role="navigation"
           aria-label="Menu principal">
        @include('layouts.partials.evon-sidebar')
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto">
        <!-- Header -->
        <header class="evon-header" role="banner">
            <!-- Mobile Menu Button -->
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="evon-mobile-menu-btn mr-4"
                    aria-label="Ouvrir le menu"
                    :aria-expanded="sidebarOpen.toString()">
                <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <!-- Desktop Header Content -->
            <div class="evon-hide-mobile items-center justify-between w-full">
                @include('layouts.partials.evon-header')
            </div>
            
            <!-- Mobile Header Content -->
            <div class="evon-show-mobile flex items-center gap-2 flex-1 justify-end">
                <div class="evon-header-utilities">
                    @if(auth()->check())
                        <!-- Mobile Notifications -->
                        <button class="evon-header-button relative" 
                                title="Notifications"
                                aria-label="Notifications">
                            @include('components.icons.lucide-bell', ['size' => 18, 'class' => 'text-gray-600 dark:text-gray-400'])
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="evon-notification-badge"></span>
                            @endif
                        </button>
                        
                        <!-- Mobile User Avatar -->
                        <button class="evon-header-avatar" 
                                title="Menu utilisateur"
                                aria-label="Menu utilisateur">
                            <span class="evon-header-avatar-text">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main id="main-content" class="evon-main" role="main" tabindex="-1">
            <div class="evon-content">
                <!-- Flash Messages -->
                @include('layouts.partials.flash-messages')
                
                <!-- Page Content -->
                @if(View::hasSection('content'))
                    @yield('content')
                @else
                    <div class="card">
                        <h1 class="heading-2 mb-3">{{ __('messages.welcome_to_evon') }}</h1>
                        <p class="body-medium text-gray-600 dark:text-gray-300 mb-6">
                            {{ __('messages.main_content_description') }}
                        </p>
                        <x-evon.button variant="primary" href="{{ route('dashboard') }}">
                            {{ __('messages.go_to_dashboard') }}
                        </x-evon.button>
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>

