<!-- Sidebar Component for Laravel (partials/sidebar.blade.php) -->
<nav class="sidebar-nav" :class="{ 'collapsed': sidebarCollapsed }">
    <!-- Logo Section -->
    <div class="sidebar-header">
        <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : ''">
            <img src="{{ asset('images/logo2.png') }}" alt="AVON Logo" class="h-8 w-8 rounded-lg">
            <span class="sidebar-text ml-3 text-xl font-bold text-gray-900 dark:text-white" 
                  x-show="!sidebarCollapsed">
                AVON
            </span>
        </div>
        
        <!-- Desktop Collapse Toggle -->
        <button @click="toggleSidebar()" class="hidden lg:block sidebar-toggle">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <!-- SECTION TABLEAU DE BORD -->
        <div class="nav-section">
            <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
                Tableau de bord
            </h3>
            
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Dashboard</span>
            </a>
        </div>

        <!-- SECTION GESTION -->
        <div class="nav-section mt-6">
            <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
                Gestion
            </h3>
            
            <!-- Intégrateurs (pour les administrateurs uniquement) -->
            @if(auth()->user()->hasRole(['admin', 'super-admin', 'Admin', 'Super-Admin', 'super_admin']))
            <a href="{{ route('integrators.index') }}"
               class="nav-item {{ request()->routeIs('integrators.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Intégrateurs</span>
            </a>
            @endif

            <!-- Groupes -->
            <a href="{{ route('groups.index') }}" 
               class="nav-item {{ request()->routeIs('groups.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Groupes</span>
            </a>

            <!-- Points de charge -->
            @can('view_charging_points')
            <a href="{{ route('charging-points.index') }}" 
               class="nav-item {{ request()->routeIs('charging-points.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Points de charge</span>
            </a>
            @endcan

            <!-- Stations -->
            @can('view_stations')
            <a href="{{ route('stations.index') }}" 
               class="nav-item {{ request()->routeIs('stations.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Stations</span>
            </a>
            @endcan

            <!-- Plans (toujours visible) -->
            <a href="{{ route('plans.index') }}" 
               class="nav-item {{ request()->routeIs('plans.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Plans</span>
            </a>

            <!-- Réservations -->
            <a href="{{ route('reservations.index') }}" 
               class="nav-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Réservations</span>
            </a>

            <!-- Business Profiles -->
            @php
                $userRolesLower = auth()->user()->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
                $isOperator = in_array('operator', $userRolesLower);
                $isPartner = in_array('partner', $userRolesLower);
            @endphp
            @can('view_business_profiles')
            @if(!$isOperator && !$isPartner)
            <a href="{{ route('business-profiles.index') }}" 
               class="nav-item {{ request()->routeIs('business-profiles.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Business Profils</span>
            </a>
            @endif
            @endcan


            <!-- Utilisateurs (Admin only) / Opérateurs (Integrators) -->
            @if(auth()->user()->hasRole(['admin', 'super-admin', 'Admin', 'Super-Admin', 'super_admin']))
                <!-- Admin sees all users -->
                @can('manage_users')
                <a href="{{ route('admin.users.index') }}"
                   class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Utilisateurs</span>
                </a>
                @endcan
            @elseif(auth()->user()->hasRole(['integrator', 'Integrator']))
                <!-- Integrators see only operators -->
                @can('view_users')
                <a href="{{ route('admin.users.index') }}"
                   class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Opérateurs</span>
                </a>
                @endcan
            @else
                <!-- Other roles -->
                @can('manage_users')
                <a href="{{ route('admin.users.index') }}"
                   class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Utilisateurs</span>
                </a>
                @endcan
            @endif

            <!-- Transactions -->
            @can('view_transactions')
            <a href="{{ route('transactions.index') }}" 
               class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Transactions</span>
            </a>
            @endcan

            <!-- Rapports -->
            @can('view_reports')
            <a href="{{ route('reports.index') }}"
               class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Rapports</span>
            </a>
            @endcan


        </div>

        <!-- SECTION PARAMÈTRES -->
        <div class="nav-section mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
            <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
                Paramètres
            </h3>
            
            <!-- Général -->
            <a href="{{ route('settings.general') }}" 
               class="nav-item {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="sidebar-text" x-show="!sidebarCollapsed">Général</span>
            </a>
        </div>
    </div>
</nav>

<style>
/* Sidebar Styles */
.sidebar {
    @apply fixed top-16 left-0 bottom-0 w-56 bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 z-30;
    @apply transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out;
    @apply overflow-y-auto overflow-x-hidden;
}

.sidebar.open {
    @apply translate-x-0;
}

.sidebar.collapsed {
    @apply lg:w-10;
}

.sidebar-nav {
    @apply h-full flex flex-col;
}

.sidebar-header {
    @apply p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between;
}

.sidebar-toggle {
    @apply p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-primary;
    @apply transition-colors duration-200;
}

.sidebar-menu {
    @apply flex-1 px-3 py-6 space-y-1;
}

.nav-section {
    @apply space-y-1;
}

.nav-item {
    @apply group flex items-center px-3 py-2 text-sm font-medium rounded-lg;
    @apply text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-primary;
    @apply transition-all duration-200;
    @apply focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2;
}

.nav-item.active {
    @apply bg-primary/10 text-primary border-r-2 border-primary;
}

.nav-item:hover .nav-icon {
    @apply text-primary;
}

.nav-item.active .nav-icon {
    @apply text-primary;
}

.nav-icon {
    @apply h-5 w-5 text-gray-500 group-hover:text-primary mr-3 flex-shrink-0;
    @apply transition-colors duration-200;
}

.sidebar-text {
    @apply transition-all duration-300;
}

.sidebar.collapsed .sidebar-text {
    @apply lg:opacity-0 lg:w-0 lg:overflow-hidden;
}

.sidebar.collapsed .nav-item {
    @apply lg:justify-center lg:px-2;
}

.sidebar.collapsed .nav-icon {
    @apply lg:mr-0;
}

/* Content area adjustment */
.content {
    @apply ml-0 lg:ml-56 pt-16 transition-all duration-300;
}

.content.collapsed {
    @apply lg:ml-16;
}
</style>