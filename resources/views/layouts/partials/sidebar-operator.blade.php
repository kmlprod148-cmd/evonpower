<!-- SECTION GESTION OPÉRATEUR -->
<div class="nav-section mt-6">
    <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
        Gestion
    </h3>
    
    <!-- Groupes -->
    <a href="{{ route('groups.index') }}" 
       class="nav-item {{ request()->routeIs('groups.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Groupes</span>
    </a>

    <!-- Points de charge -->
    <a href="{{ route('operator.charging-points.index') }}"
       class="nav-item {{ request()->routeIs('operator.charging-points.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Points de charge</span>
    </a>

    <!-- Stations -->
    <a href="{{ route('stations.index') }}" 
       class="nav-item {{ request()->routeIs('stations.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Stations</span>
    </a>

    <!-- Plans tarifaires -->
    <a href="{{ route('operator.pricing-plans.index') }}"
       class="nav-item {{ request()->routeIs('operator.pricing-plans.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Plans tarifaires</span>
    </a>

    <!-- Partenaires -->
    <a href="{{ route('partners.index') }}" 
       class="nav-item {{ request()->routeIs('partners.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V8a2 2 0 01-2 2H6a2 2 0 01-2-2V6m8 0H8m0 0v2m0 0V4m0 4h8m-8 0H4m4 0V4"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Partenaires</span>
    </a>

    <!-- Transactions -->
    <a href="{{ route('transactions.index') }}" 
       class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Transactions</span>
    </a>

    <!-- Réservations -->
    <a href="{{ route('reservations.index') }}" 
       class="nav-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Réservations</span>
    </a>

    <!-- Rapports -->
    <a href="{{ route('reports.index') }}" 
       class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span class="sidebar-text" x-show="!sidebarCollapsed">Rapports</span>
    </a>
</div>
