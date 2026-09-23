{{-- Dropdown menu utilisateur --}}
@props(['formattedBalance' => '0.00 EUR'])

<div x-data="{ userMenuOpen: false }" class="relative">
    <button @click="userMenuOpen = !userMenuOpen" class="focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-eco-green-500 group">
        <div class="h-10 w-10 bg-gradient-to-br from-eco-green-500 to-eco-green-600 rounded-full flex items-center justify-center ring-2 ring-white dark:ring-gray-800 shadow-md group-hover:ring-eco-green-400 transition-all">
            <span class="text-sm font-medium text-white">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
        </div>
    </button>
    
    <div x-show="userMenuOpen" 
         @click.away="userMenuOpen = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-56 rounded-lg shadow-xl bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 dark:divide-gray-700 z-50"
         style="display: none;">
        <!-- User Info -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 bg-gradient-to-br from-eco-green-500 to-eco-green-600 rounded-full flex items-center justify-center ring-2 ring-white dark:ring-gray-800 flex-shrink-0">
                    <span class="text-sm font-medium text-white">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 bg-gradient-to-r from-eco-green-50 to-eco-green-100 dark:from-eco-green-900/20 dark:to-eco-green-900/20 rounded-lg p-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-eco-green-600 dark:text-eco-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Solde Wallet</span>
                    </div>
                    <span class="text-base font-bold text-eco-green-600 dark:text-eco-green-400" data-balance-value>{{ $formattedBalance }}</span>
                </div>
            </div>
        </div>
        
        <!-- Menu Items -->
        <div class="py-1">
            <a href="{{ route('credit-recharge.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-eco-green-50 dark:hover:bg-eco-green-900/20 hover:text-eco-green-600 dark:hover:text-eco-green-400 transition-colors">
                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Recharger mon wallet
            </a>
            <a href="{{ route('subscriptions.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Mes Abonnements
            </a>
            <a href="{{ route('reservations.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Mes Réservations
            </a>
        </div>
        
        <!-- Logout -->
        <div class="py-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                    <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('messages.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>

