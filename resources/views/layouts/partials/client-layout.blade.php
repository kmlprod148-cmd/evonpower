<!-- Layout pour clients publics - SIDEBAR COMPLÈTEMENT CACHÉE ET DÉSACTIVÉE -->
<div class="min-h-screen flex flex-col">
    <!-- Top Navigation Bar pour clients -->
    <nav class="evon-header" role="banner">
        <div class="flex items-center justify-between w-full px-4 sm:px-6 lg:px-8">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 rounded-lg">
                    @if(file_exists(public_path('images/evon-logo.png')))
                        <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" class="h-8 w-auto">
                    @else
                        <div class="h-8 w-8 bg-gradient-to-br from-eco-green-500 to-eco-green-600 rounded-lg flex items-center justify-center shadow-md">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    @endif
                    <span class="text-xl font-semibold text-gray-900 dark:text-white">
                        @yield('page-title', __('Mes Réservations'))
                    </span>
                </a>
            </div>
            
            <!-- Right side utilities -->
            <div class="flex items-center gap-3">
                <!-- Language Switcher -->
                @if(View::exists('components.direct-language-switcher'))
                    @include('components.direct-language-switcher')
                @endif
                
                @php
                    // Solde à jour depuis le ViewComposer global (user_formatted_balance)
                    $formattedBalance = $user_formatted_balance ?? '0.00 EUR';
                @endphp
                
                <!-- Balance Display - data-balance-updatable pour mise à jour après paiement -->
                <a href="{{ route('credit-recharge.index') }}" 
                   id="client-wallet-balance"
                   data-balance-updatable="true"
                   class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800 hover:shadow-md transition-all"
                   title="Cliquez pour recharger votre wallet"
                   aria-label="Balance: {{ $formattedBalance }}">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Solde</span>
                        <span class="text-sm font-bold text-green-600 dark:text-green-400" data-balance-value>{{ $formattedBalance }}</span>
                    </div>
                </a>
                
                <!-- User Dropdown -->
                <div x-data="{ userMenuOpen: false }" class="relative">
                    <button @click="userMenuOpen = !userMenuOpen" 
                            class="evon-header-avatar"
                            :aria-expanded="userMenuOpen.toString()"
                            aria-label="Menu utilisateur">
                        <span class="evon-header-avatar-text">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                    </button>
                    
                    <div x-show="userMenuOpen" 
                         x-cloak
                         @click.away="userMenuOpen = false"
                         @keydown.escape.window="userMenuOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="dropdown-menu"
                         role="menu">
                        <!-- User Info -->
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ Auth::user()->name ?? 'Utilisateur' }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                {{ Auth::user()->email ?? '' }}
                            </p>
                            <div class="mt-2 px-2 py-1 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded text-center">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Solde: </span>
                                <span class="text-sm font-bold text-green-600 dark:text-green-400" data-balance-value>{{ $formattedBalance }}</span>
                            </div>
                        </div>
                        
                        <!-- Menu Items -->
                        <div class="py-1">
                            <a href="{{ route('credit-recharge.index') }}" class="dropdown-item">
                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Recharger mon wallet
                            </a>
                            <a href="{{ route('subscriptions.index') }}" class="dropdown-item">
                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Mes Abonnements
                            </a>
                            <a href="{{ route('reservations.index') }}" class="dropdown-item">
                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Mes Réservations
                            </a>
                        </div>
                        
                        <!-- Logout -->
                        <div class="py-1">
                            <form method="POST" action="{{ route('logout.client') }}">
                                @csrf
                                <button type="submit" class="dropdown-item hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 dark:hover:text-red-400">
                                    <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    {{ __('messages.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content pour clients -->
    <main id="main-content" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900" role="main" tabindex="-1">
        <div class="py-6 sm:py-8">
            <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @include('layouts.partials.flash-messages')
                
                <!-- Page Content -->
                @if(View::hasSection('content'))
                    @yield('content')
                @else
                    <div class="card">
                        <h1 class="heading-2 mb-4">{{ __('Mes Réservations') }}</h1>
                        <p class="body-medium text-gray-600 dark:text-gray-300">
                            {{ __('Gérez vos réservations de recharge') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </main>
</div>

