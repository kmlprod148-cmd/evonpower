<!-- Mobile Navigation Component -->
<nav class="mobile-nav lg:hidden" x-show="mobileMenuOpen" x-transition>
    <!-- Header mobile avec logo de marque -->
    <div class="mobile-nav-header bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center">
                <img src="{{ \App\Helpers\Brand::logo() }}" 
                     alt="{{ \App\Helpers\Brand::name() }} Logo" 
                     class="h-8 w-8 rounded-lg object-contain">
                <span class="ml-3 text-lg font-bold text-gray-900 dark:text-white">
                    {{ \App\Helpers\Brand::name() }}
                </span>
            </div>
            <button @click="mobileMenuOpen = false" 
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Menu mobile -->
    <div class="mobile-nav-menu bg-white dark:bg-gray-800">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                </svg>
                <span>Dashboard</span>
            </a>
            
            <!-- Points de charge -->
            @can('view_charging_points')
            <a href="{{ route('charging-points.index') }}" 
               class="mobile-nav-item {{ request()->routeIs('charging-points.*') ? 'active' : '' }}">
                <svg class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>Points de charge</span>
            </a>
            @endcan
            
            <!-- Transactions -->
            @can('view_transactions')
            <a href="{{ route('transactions.index') }}" 
               class="mobile-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <svg class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span>Transactions</span>
            </a>
            @endcan
            
            <!-- Paramètres -->
            <a href="{{ route('settings.index') }}" 
               class="mobile-nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <svg class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Paramètres</span>
            </a>
            
            <!-- Profil -->
            <a href="{{ route('profile.edit') }}" 
               class="mobile-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <svg class="mobile-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Mon Profil</span>
            </a>
        </div>
    </div>
</nav>